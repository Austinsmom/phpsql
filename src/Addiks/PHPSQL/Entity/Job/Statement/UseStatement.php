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

namespace Addiks\Database\Entity\Job\Statement;

use Addiks\Database\Entity\Job\Statement;
use Addiks\Database\Service\Executor\UseExecutor;

/**
 * 
 * @Addiks\Statement(executorClass="UseExecutor")
 * @author gerrit
 *
 */
class UseStatement extends Statement{
	
	private $database;
	
	public function setDatabase(Database $database){
		$this->database = $database;
	}
	
	public function getDatabase(){
		return $this->database;
	}

	public function getResultSpecifier(){
	}
	
}