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

use Addiks\PHPSQL\Executor;
use Addiks\PHPSQL\Entity\Result\Temporary;
use Addiks\PHPSQL\Database;
use Addiks\PHPSQL\StatementExecutor\StatementExecutorInterface;
use Addiks\PHPSQL\Entity\Job\Statement\SetStatement;
use Addiks\PHPSQL\Entity\Job\StatementJob;

class SetExecutor implements StatementExecutorInterface
{
    
    public function canExecuteJob(StatementJob $statement)
    {
        return $statement instanceof SetStatement;
    }

    public function executeJob(StatementJob $statement, array $parameters = array())
    {
        /* @var $statement SetStatement */
        
        # TODO: implement this
        
        $result = new TemporaryResult();
        return $result;
    }
}