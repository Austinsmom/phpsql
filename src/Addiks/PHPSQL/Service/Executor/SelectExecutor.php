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

namespace Addiks\Database\Service\Executor;

use Addiks\Database\Entity\Job\Statement\Select as SelectStatement;

use Addiks\Database\Resource\Database;

use Addiks\Database\Resource\SelectResult;

use Addiks\Database\Service\Executor;

class SelectExecutor extends Executor{
	
	protected function executeConcreteJob($statement, array $parameters=array()){
		/* @var $statement SelectStatement */
		
		/* @var $databaseResource Database */
		$this->factorize($databaseResource);
		
		/* @var $result SelectResult */
		$this->factorize($result, [$statement, $parameters]);
		
		return $result;
	}
	
}