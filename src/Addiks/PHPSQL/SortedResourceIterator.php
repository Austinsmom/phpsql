<?php
/**
 * Copyright (C) 2013  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 * @package Addiks
 */

namespace Addiks\PHPSQL;

use Addiks\PHPSQL\Entity\Index\QuickSort;
use Addiks\PHPSQL\Value\Enum\Page\Column\DataType;
use Addiks\PHPSQL\Value\Enum\Sql\SqlToken;
use Addiks\PHPSQL\Entity\Page\Column;
use Addiks\PHPSQL\Table;
use Addiks\PHPSQL\CustomIterator;
use Addiks\PHPSQL\Entity\Index\IndexInterface;
use Addiks\PHPSQL\Entity\Result\ResultInterface;
use Countable;
use SeekableIterator;
use Addiks\PHPSQL\Entity\Index\QuickSort;
use Addiks\PHPSQL\Filesystem\FilesystemInterface;

/**
 * The purspose of this component is to iterate sorted over one data-source.
 * the sortion can be done with (sometimes temporary) indexes/iterators.
 *
 * TODO: this iterator can currently only iterate over one index, implement the rest!
 *
 */
class SortedResourceIterator implements Countable, SeekableIterator
{

    public function __construct(
        $resource,
        FilesystemInterface $filesystem,
        ValueResolver $valueResolver
    ) {
        $this->filesystem = $filesystem;
        $this->valueResolver = $valueResolver;
        if ($resource instanceof Table) {
            $this->setResourceTable($resource);
        } elseif ($resource instanceof ResultInterface) {
            $this->setResourceResult($resource);
        } else {
            throw new ErrorException("Resource for SortedResourceIterator has to be Table or ResultInterface!");
        }
    }

    private $filesystem;

    public function getFilesystem()
    {
        return $this->filesystem;
    }

    private $valueResolver;

    public function getValueResolver()
    {
        return $this->valueResolver;
    }

    private $resource;

    public function setResourceTable(Table $table)
    {
        $this->resource = $table;
    }

    public function setResourceResult(ResultInterface $result)
    {
        $this->resource = $result;
    }

    public function getResource()
    {
        return $this->resource;
    }

    private $iterator;

    public function setChildIterator(\Iterator $iterator)
    {
        $this->iterator = $iterator;
    }
    
    public function getSortIndexByColumns(array $orderColumns)
    {
        
        $uniqid = uniqid();
        $filePath = "TempTables/{$uniqid}";
        $insertionSortFile = $this->getCache($filePath);
        $insertionSortFile->setIsTemporary(true);
        
        $columnPage = new ColumnPage();
            
        $columnPages = array();
        foreach ($orderColumns as $columnIndex => $columnDataset) {
            if ($columnDataset instanceof Column) {
                $direction = 'ASC';
                $columnPage = $columnDataset;
                
            } else {
                $direction = $columnDataset['direction'] === SqlToken::T_ASC() ?'ASC' :'DESC';
                
                /* @var $value Value */
                $value = $columnDataset['value'];
                
                $columnPage->setDataType(DataType::VARCHAR());
                $columnPage->setLength(64);
                $columnPage->setName("INDEXVALUE_{$columnIndex}");
            }
            
            $columnPages[] = [
                clone $columnPage,
                $direction
            ];
        }
        
        $sortIndex = new QuickSort($insertionSortFile, $columnPages);
        
        return $sortIndex;
    }
    
    public function setTemporaryBuildChildIteratorByValue(array $orderColumns, ResultInterface $dataSource)
    {
        
        /* @var $sortIndex QuickSort */
        $sortIndex = $this->getSortIndexByColumns($orderColumns);
        
        /* @var $valueResolver ValueResolver */
        $valueResolver = $this->valueResolver;

        $columnPage = false;
        
        $indexAlreadyBuilt = false;
        
        $rebuildIndex = function () use ($orderColumns, $dataSource, $sortIndex, $valueResolver) {
            
            switch(true){
                
                case $dataSource instanceof JoinIterator:
                    $iterator = $dataSource->getUnsortedIterator();
                    break;
                
                default:
                    $iterator = $dataSource->getIterator();
                    break;
            }
            
            $dataSource->rewind();
            foreach ($iterator as $rowId => $rows) {
                $mergedRow = array();
                foreach ($rows as $alias => $sourceRow) {
                    foreach ($sourceRow as $columnName => $cell) {
                        $mergedRow[$columnName] = $cell;
                        $mergedRow["{$alias}.{$columnName}"] = $cell;
                    }
                }
                
                $valueResolver->setSourceRow($mergedRow);
                
                $insertRow = array();
                foreach ($orderColumns as $columnIndex => $columnDataset) {
                    $value = $columnDataset['value'];
                    /* @var $value Value */
                    
                    $insertRow[$columnIndex] = $valueResolver->resolveValue($value);
                }
                
                $sortIndex->addRow($rowId, $insertRow);
            }
            
            $sortIndex->sort();
        };
        
        $iterator = new CustomIterator(null, [
            'rewind' => function () use (&$indexAlreadyBuilt, $rebuildIndex, $sortIndex) {
                if (!$indexAlreadyBuilt) {
                    $indexAlreadyBuilt = true;
                    $rebuildIndex();
                }
                $sortIndex->rewind();
            },
            'valid' => function () use ($sortIndex) {
                return $sortIndex->valid();
            },
            'current' => function () use ($sortIndex) {
                return $sortIndex->current();
            },
            'key' => function () use ($sortIndex) {
                return $sortIndex->key();
            },
            'next' => function () use ($sortIndex) {
                $sortIndex->next();
            },
        ]);
            
        $this->setChildIterator($iterator);
    }

    public function getChildIterator()
    {
        return $this->iterator;
    }

    public function getCurrentRowId()
    {
        
        /* @var $iterator \Iterator*/
        $iterator = $this->getChildIterator();
        
        if (!$iterator->valid()) {
            $rowId = null;
        
        } else {
            $rowId = $iterator->current();
        
            if (is_array($rowId)) {
                $rowId = reset($rowId);
            }
        }
        
        return $rowId;
    }
    
    public function getIterator()
    {
        return $this;
    }

    public function rewind()
    {
        
        $this->getChildIterator()->rewind();
        
        if ($this->getChildIterator()->valid()) {
            $this->syncResourceToIterator();
        }
    }

    public function valid()
    {
        
        return $this->getChildIterator()->valid();
    }

    public function current()
    {

        $resource = $this->getResource();

        switch(true){

            case $resource instanceof Table:
                $row = $resource->getIterator()->current();
                if (is_array($row)) {
                    $row = $resource->convertDataRowToStringRow($row);
                }
                break;

            case $resource instanceof SelectResult:
                $row = current($resource);
                break;

            default:
                throw new \ErrorException("Invalid table-source type!");
        }

        return $row;
    }

    public function key()
    {
        return $this->getCurrentRowId();
    }

    public function next()
    {

        /* @var $iterator \Iterator*/
        $iterator = $this->getChildIterator();
        
        $iterator->next();

        $this->syncResourceToIterator();
    }
    
    public function count()
    {
        
        $resource = $this->getResource();
        
        return $resource->count();
    }
    
    public function seek($rowId)
    {
        
        /* @var $iterator \Iterator*/
        $iterator = $this->getChildIterator();
        
    #	var_dump(
    #       $iterator->getConstructorTrace(),
    #       $iterator->getInnerIterator()->getConstructorTrace(),
    #       $iterator->getInnerIterator()->getInnerIterator()->getArrayCopy(),
    #		$iterator instanceof \ArrayIterator
    #	);
        
        switch(true){
            
            case $iterator instanceof CustomIterator:
            case $iterator instanceof \ArrayIterator:
                $iterator->seek($rowId);
                break;
                
            default:
                $iterator->seek($rowId);
                break;
        }
        
        $this->syncResourceToIterator();
    }
    
    protected function syncResourceToIterator()
    {
        
        $resource = $this->getResource();
        
        /* @var $iterator \Iterator*/
        $iterator = $this->getChildIterator();
        
        if (!$iterator->valid()) {
            $rowId = null;
                
        } else {
            $rowId = $iterator->current();
            
            if (is_array($rowId)) {
                $rowId = reset($rowId);
            }
        }
        
        switch(true){
        
            case $resource instanceof Table:
                $resource->seek($rowId);
                break;
        
            case $resource instanceof SelectResult:
                $resource->seek($rowId);
                break;
        }
    }
}
