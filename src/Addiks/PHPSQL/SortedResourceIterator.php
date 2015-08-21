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
use Addiks\PHPSQL\Entity\Page\ColumnPage;
use Addiks\PHPSQL\CustomIterator;
use Addiks\PHPSQL\Entity\Index\IndexInterface;
use Addiks\PHPSQL\Entity\Result\ResultInterface;
use Countable;
use SeekableIterator;
use ErrorException;
use IteratorAggregate;
use Iterator;
use Addiks\PHPSQL\Filesystem\FilesystemInterface;
use Addiks\PHPSQL\Filesystem\FileResourceProxy;
use Addiks\PHPSQL\Entity\ExecutionContext;

/**
 * The purspose of this component is to iterate sorted over one data-source.
 * the sortion can be done with (sometimes temporary) indexes/iterators.
 *
 * TODO: this iterator can currently only iterate over one index, implement the rest!
 *
 */
class SortedResourceIterator implements Countable, SeekableIterator, UsesBinaryDataInterface
{

    public function __construct(
        Iterator $resource,
        ValueResolver $valueResolver
    ) {
        $this->resource = $resource;
        $this->valueResolver = $valueResolver;
    }

    private $valueResolver;

    public function getValueResolver()
    {
        return $this->valueResolver;
    }

    private $resource;

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
        $insertionSortFile = new FileResourceProxy(fopen("php://memory", "w"));
        
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
                
                $columnPage->setDataType(DataType::VARCHAR()); # TODO: get actual column-page if possible
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
    
    public function setTemporaryBuildChildIteratorByValue(
        array $orderColumns,
        ResultInterface $dataSource,
        ExecutionContext $context
    ) {
        
        /* @var $sortIndex QuickSort */
        $sortIndex = $this->getSortIndexByColumns($orderColumns);
        
        /* @var $valueResolver ValueResolver */
        $valueResolver = $this->valueResolver;

        $columnPage = false;
        
        $indexAlreadyBuilt = false;
        
        $rebuildIndex = function () use ($orderColumns, $dataSource, $sortIndex, $valueResolver, $context) {
            
            switch(true){
                
                case $dataSource instanceof JoinIterator:
                    $iterator = $dataSource->getUnsortedIterator();
                    break;
                
                default:
                    $iterator = $dataSource->getIterator();
                    break;
            }

            $indexBuildContext = clone $context;
            
            $dataSource->rewind();
            foreach ($iterator as $rowId => $rows) {
                #$mergedRow = array();
                #foreach ($rows as $alias => $sourceRow) {
                #    foreach ($sourceRow as $columnName => $cell) {
                #        $mergedRow[$columnName] = $cell;
                #        $mergedRow["{$alias}.{$columnName}"] = $cell;
                #    }
                #}
                
                $indexBuildContext->setCurrentSourceRow($rows);
                
                $insertRow = array();
                foreach ($orderColumns as $columnIndex => $columnDataset) {
                    $value = $columnDataset['value'];
                    /* @var $value Value */
                    
                    $insertRow[$columnIndex] = $valueResolver->resolveValue($value, $indexBuildContext);
                }
                
                $sortIndex->addRow($rowId, $insertRow);
            }
            
            $sortIndex->sort();
        };
        
        $iterator = new CustomIterator($this, [
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

            case $resource instanceof Iterator:
                $row = $resource->current();
                break;

            case $resource instanceof IteratorAggregate:
                $row = $resource->getIterator()->current();
                break;

            default:
                throw new ErrorException("Invalid table-source type!");
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
        
        switch(true){
            
            case $iterator instanceof SeekableIterator:
                $iterator->seek($rowId);
                break;
                
            default:
                throw new ErrorException("Invalid resource to seek!");
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
        
            case $resource instanceof SeekableIterator:
                $resource->seek($rowId);
                break;

            default:
                throw new ErrorException("Invalid resource to seek to 0!");
        }
    }

    public function usesBinaryData()
    {
        $isBinary = false;
        if ($this->tableResource instanceof UsesBinaryDataInterface) {
            $isBinary = $this->tableResource->usesBinaryData();
        }
        return $isBinary;
    }

    public function convertDataRowToStringRow(array $row)
    {
        if ($this->tableResource instanceof UsesBinaryDataInterface) {
            $row = $this->tableResource->convertDataRowToStringRow($row);
        }
        return $row;
    }

    public function convertStringRowToDataRow(array $row)
    {
        if ($this->tableResource instanceof UsesBinaryDataInterface) {
            $row = $this->tableResource->convertStringRowToDataRow($row);
        }
        return $row;
    }

}
