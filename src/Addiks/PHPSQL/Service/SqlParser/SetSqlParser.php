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

namespace Addiks\Database\Service\SqlParser;

use Addiks\Database\Entity\Job\Statement\SetStatement;

use Addiks\Database\Entity\Exception\MalformedSql;
use Addiks\Database\Value\Enum\Sql\SqlToken;
use Addiks\Analyser\Tool\TokenIterator;

use Addiks\Database\Tool\SQLTokenIterator;

use Addiks\Database\Service\SqlParser;

class SetSqlParser extends SqlParser{
	
	public function canParseTokens(SQLTokenIterator $tokens){
		return is_int($tokens->isTokenNum(SqlToken::T_SET(), TokenIterator::CURRENT))
		    || is_int($tokens->isTokenNum(SqlToken::T_SET(), TokenIterator::NEXT));
	}
	
	public function convertSqlToJob(SQLTokenIterator $tokens){
		
		$tokens->seekTokenNum(SqlToken::T_SET());
		
		if($tokens->getCurrentTokenNumber() !== SqlToken::T_SET()){
			throw new Error("Tried to parse SET statement when token-iterator is not at T_SET!");
		}
		
		/* @var $valueParser ValueParser */
		$this->factorize($valueParser);
		
		/* @var $setJob SetStatement */
		$this->factorize($setJob);
		
		if(!$tokens->seekTokenNum(T_STRING)){
			throw new MalformedSql("Missing configuration name for SET statement!", $tokens);
		}
		
		$setJob->setKey($tokens->getCurrentTokenString());
		
		$tokens->seekTokenText('=');
		
		if(!$valueParser->canParseTokens($tokens)){
			throw new MalformedSql("Missing valid value definition for SET statement!", $tokens);
		}
		
		$setJob->setValue($valueParser->convertSqlToJob($tokens));
		
		return $setJob;
	}
	
}