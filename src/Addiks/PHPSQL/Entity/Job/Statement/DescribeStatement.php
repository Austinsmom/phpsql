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

namespace Addiks\PHPSQL\Entity\Job\Statement;

use Addiks\PHPSQL\Value\Specifier\TableSpecifier;
use Addiks\PHPSQL\Executor\DescribeExecutor;
use Addiks\PHPSQL\Entity\Job\StatementJob;

/**
 *
 */
class DescribeStatement extends StatementJob
{
    
    const EXECUTOR_CLASS = DescribeExecutor::class;

    private $table;
    
    public function setTable(TableSpecifier $table)
    {
        $this->table = $table;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    private $column;
    
    public function setColumnName($column)
    {
        $this->column = (string)$column;
    }
    
    public function getColumnName()
    {
        return $this->column;
    }
    
    private $isWild;
    
    public function setIsWild($bool)
    {
        $this->isWild = (bool)$bool;
    }
    
    public function getIsWild()
    {
        return $this->isWild;
    }
    
    public function getResultSpecifier()
    {
        
    }
}
