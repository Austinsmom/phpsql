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

namespace Addiks\PHPSQL\StatementExecutor;

use Addiks\PHPSQL\Value\Enum\Sql\Alter\DataChange\AlterAttributeType;
use Addiks\PHPSQL\Entity\Result\Temporary;
use Addiks\PHPSQL\Database;
use Addiks\PHPSQL\Filesystem\FilesystemInterface;
use Addiks\PHPSQL\ValueResolver;
use Addiks\PHPSQL\Table\TableManager;
use Addiks\PHPSQL\Entity\Result\TemporaryResult;
use Addiks\PHPSQL\Entity\Job\Statement\AlterStatement;
use Addiks\PHPSQL\Entity\Job\StatementJob;
use Addiks\PHPSQL\Schema\SchemaManager;
use Addiks\PHPSQL\Entity\ExecutionContext;
use Addiks\PHPSQL\Entity\Job\Part\ColumnDefinition;
use Addiks\PHPSQL\Entity\Page\SchemaPage as SchemaPage;
use Addiks\PHPSQL\Table\TableInterface;

class AlterExecutor implements StatementExecutorInterface
{
    
    public function __construct(
        SchemaManager $schemaManager,
        TableManager $tableManager
    ) {
        $this->schemaManager = $schemaManager;
        $this->tableManager = $tableManager;
    }

    protected $schemaManager;

    public function getSchemaManager()
    {
        return $this->schemaManager;
    }

    protected $tableManager;

    public function getTableManager()
    {
        return $this->tableManager;
    }
    
    public function canExecuteJob(StatementJob $statement)
    {
        return $statement instanceof AlterStatement;
    }

    public function executeJob(StatementJob $statement, array $parameters = array())
    {
        /* @var $statement AlterStatement */
        
        $executionContext = new ExecutionContext(
            $this->schemaManager,
            $statement,
            $parameters
        );

        /* @var $tableSpecifier TableSpecifier */
        $tableSpecifier = $statement->getTable();
        
        /* @var $tableResource TableInterface */
        $tableResource = $this->tableManager->getTable(
            $tableSpecifier->getTable(),
            $tableSpecifier->getDatabase()
        );

        /* @var $tableSchema TableSchema */
        $tableSchema = $tableResource->getTableSchema();
        
        foreach ($statement->getDataChanges() as $dataChange) {
            /* @var $dataChange DataChange */
            
            switch($dataChange->getAttribute()){
                
                case AlterAttributeType::ADD():
                    /* @var $columnDefinition ColumnDefinition */
                    $columnDefinition = $dataChange->getSubject();
                    
                    $tableResource->addColumnDefinition($columnDefinition, $executionContext);
                    break;
                    
                case AlterAttributeType::DROP():
                    /* @var $columnSpecifier ColumnSpecifier */
                    $columnSpecifier = $dataChange->getSubject();
                    
                    $columnId = $tableSchema->getColumnIndex($columnSpecifier->getColumn());
                    
                    $tableSchema->removeColumn($columnId);
                    break;
                        
                case AlterAttributeType::SET_AFTER():
                case AlterAttributeType::SET_FIRST():
                case AlterAttributeType::MODIFY():
                    /* @var $columnDefinition ColumnDefinition */
                    $columnDefinition = $dataChange->getSubject();
                    
                    $tableResource->modifyColumnDefinition($columnDefinition, $executionContext);

                    if ($dataChange->getAttribute() === AlterAttributeType::SET_FIRST()) {
                        $subjectColumnIndex = $tableSchema->getColumnIndex($columnDefinition->getName());
                        $subjectColumnPage = $tableSchema->getColumn($subjectColumnIndex);
                        $oldIndex = $subjectColumnPage->getIndex();
                        foreach ($tableSchema->getColumnIterator() as $columnIndex => $columnPage) {
                            if ($columnPage->getIndex() < $oldIndex) {
                                $columnPage->setIndex($columnPage->getIndex()+1);
                                $tableSchema->writeColumn($columnIndex, $columnPage);
                            }
                        }
                        $subjectColumnPage->setIndex(0);
                        $tableSchema->writeColumn($subjectColumnIndex, $subjectColumnPage);

                    } elseif($dataChange->getAttribute() === AlterAttributeType::SET_AFTER()) {
                        /* @var $afterColumn ColumnSpecifier */
                        $afterColumn = $dataChange->getValue();

                        $afterColumnIndex = $tableSchema->getColumnIndex($afterColumn->getColumn());
                        $afterColumnPage = $tableSchema->getColumn($afterColumnIndex);
                        $subjectColumnIndex = $tableSchema->getColumnIndex($columnDefinition->getName());
                        $subjectColumnPage = $tableSchema->getColumn($subjectColumnIndex);

                        if ($afterColumnPage->getIndex() < $subjectColumnPage->getIndex()) {
                            foreach ($tableSchema->getColumnIterator() as $columnIndex => $columnPage) {
                                if ($columnPage->getIndex() > $afterColumnPage->getIndex()
                                &&  $columnPage->getIndex() < $subjectColumnPage->getIndex()) {
                                    $columnPage->setIndex($columnPage->getIndex()+1);
                                    $tableSchema->writeColumn($columnIndex, $columnPage);
                                }
                            }
                            $subjectColumnPage->getIndex($afterColumnPage->getIndex() + 1);
                            $tableSchema->writeColumn($subjectColumnIndex, $subjectColumnPage);

                        } else {
                            foreach ($tableSchema->getColumnIterator() as $columnIndex => $columnPage) {
                                if ($columnPage->getIndex() > $afterColumnPage->getIndex()
                                &&  $columnPage->getIndex() < $subjectColumnPage->getIndex()) {
                                    $columnPage->setIndex($columnPage->getIndex()-1);
                                    $tableSchema->writeColumn($columnIndex, $columnPage);
                                }
                            }
                            $subjectColumnPage->setIndex($afterColumnPage->getIndex());
                            $tableSchema->writeColumn($subjectColumnIndex, $subjectColumnPage);
                            $afterColumnPage->setIndex($afterColumnPage->getIndex() - 1);
                            $tableSchema->writeColumn($afterColumnPage, $afterColumnPage);
                        }
                    }
                    break;
                    
                case AlterAttributeType::RENAME():
                    $databaseSchema = $this->schemaManager->getSchema($tableSpecifier->getDatabase());
                    /* @var $tablePage SchemaPage */
                    $tableIndex = $databaseSchema->getTableIndex($tableResource->getTableName());
                    $tablePage = $databaseSchema->getTablePage($tableIndex);
                    $tablePage->setName($dataChange->getValue());
                    $databaseSchema->registerTableSchema($tablePage, $tableIndex);
                    break;
                     
                case AlterAttributeType::CHARACTER_SET():
                    break;
                    
                case AlterAttributeType::COLLATE():
                    break;
                    
                case AlterAttributeType::CONVERT():
                    break;
                
                case AlterAttributeType::DEFAULT_VALUE():
                    break;
                    
                case AlterAttributeType::ORDER_BY_ASC():
                    break;
                
                case AlterAttributeType::ORDER_BY_DESC():
                    break;
                        
            }
        }
        
        $result = new TemporaryResult();
        
        return $result;
    }
}
