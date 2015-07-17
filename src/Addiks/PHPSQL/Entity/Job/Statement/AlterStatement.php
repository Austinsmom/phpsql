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

use Addiks\PHPSQL\Entity\Job\StatementJob;
use Addiks\PHPSQL\Executor\AlterExecutor;
use Addiks\PHPSQL\Value\Specifier\TableSpecifier;
use Addiks\PHPSQL\Entity\Job\DataChange\AlterTableDataChange;

/**
 *
 */
class AlterStatement extends StatementJob
{

    const EXECUTOR_CLASS = AlterExecutor::class;

    private $table;
    
    public function setTable(TableSpecifier $table)
    {
        $this->table = $table;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    private $doIgnoreErrors = false;
    
    public function setDoIgnoreErrors($bool)
    {
        $this->doIgnoreErrors = (bool)$bool;
    }
    
    private $dataChanges = array();
    
    public function addDataChange(AlterTableDataChange $dataChange)
    {
        $this->dataChanges[] = $dataChange;
    }
    
    public function getDataChanges()
    {
        return $this->dataChanges;
    }
    
    public function getResultSpecifier()
    {
    }
}
