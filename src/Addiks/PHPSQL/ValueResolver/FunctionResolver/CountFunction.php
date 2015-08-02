<?php

namespace Addiks\PHPSQL\ValueResolver\FunctionResolver;

use Addiks\PHPSQL\ValueResolver\FunctionResolver;
use Addiks\PHPSQL\Entity\Result\ResultInterface;
use Addiks\PHPSQL\Entity\Job\FunctionJob;

class CountFunction implements AggregateInterface, FunctionResolverInterface
{
    
    public function getExpectedParameterCount()
    {
        return 1;
    }
    
    private $rowIdsInGrouping = array();
    
    public function setRowIdsInCurrentGroup(array $rowIds)
    {
        $this->rowIdsInGrouping = $rowIds;
    }
    
    private $resultSet;
    
    public function setResultSet(ResultInterface $result)
    {
        $this->resultSet = $result;
    }
    
    public function executeFunction(FunctionJob $function)
    {
        
        /* @var $result SelectResult */
        $result = $this->resultSet;
        
        /* @var $argumentValue Value */
        $argumentValue = current($function->getArguments());
        
        $count = 0;
        
        foreach ($this->rowIdsInGrouping as $rowId) {
            $row = $result->getRowUnresolved($rowId);
            
            $this->valueResolver->setSourceRow($row);
            
            $value = $this->valueResolver->resolveValue($argumentValue);
            
            if (!is_null($value)) {
                $count++;
            }
        }
        
        return $count;
    }
}
