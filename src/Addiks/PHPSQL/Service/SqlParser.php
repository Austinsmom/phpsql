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

namespace Addiks\Database\Service;

use Addiks\Database\Service\SqlParser\DescribeSqlParser;
use Addiks\Database\Service\SqlParser\SetSqlParser;
use Addiks\Database\Service\SqlParser\DropSqlParser;
use Addiks\Database\Service\SqlParser\AlterSqlParser;
use Addiks\Database\Service\SqlParser\CreateSqlParser;
use Addiks\Database\Service\SqlParser\UseSqlParser;
use Addiks\Database\Service\SqlParser\ShowSqlParser;
use Addiks\Database\Service\SqlParser\DeleteSqlParser;
use Addiks\Database\Service\SqlParser\UpdateSqlParser;
use Addiks\Database\Service\SqlParser\InsertSqlParser;
use Addiks\Database\Service\SqlParser\SelectSqlParser;
use Addiks\Database\Service\SqlParser\Part\Parenthesis;
use Addiks\Database\Value\Enum\Sql\SqlToken;
use Addiks\Database\Entity\Exception\MalformedSql;
use Addiks\Database\Tool\SQLTokenIterator;

use Addiks\Common\Service;

use Addiks\Analyser\Tool\TokenIterator;

/**
 * This is a parser for SQL statements.
 * You give it an SQL statement in form of a SQL-Token-Iterator,
 * and it either throws an MalformedSql exception or returnes an Job-Entity.
 * 
 * Technically it acts as a hub for the concrete parsers (select-parser, insert-parser, create-parser, ...).
 * 
 * The job-entity can then be rendered to a php-function executing the requested operation.
 * 
 * @see SQLTokenIterator
 * @see Job
 * @see JobRenderer
 */
class SqlParser extends Service{
	
	public function canParseTokens(SQLTokenIterator $tokens){
		return true;
	}
	
	public function convertSqlToJob(SQLTokenIterator $tokens){
		
		if(get_class($this) !== __CLASS__){
			throw new Error("Class '".get_class($this)."' needs to declare an own method '".__FUNCTION__."'!");
		}
		
		/* @var $parenthesisParser Parenthesis */
		$this->factorize($parenthesisParser);
		
		/* @var $selectParser SelectSqlParser */
		$this->factorize($selectParser);
		
		/* @var $insertParser InsertSqlParser */
		$this->factorize($insertParser);
		
		/* @var $updateParser UpdateSqlParser */
		$this->factorize($updateParser);
		
		/* @var $deleteParser DeleteSqlParser */
		$this->factorize($deleteParser);
		
		/* @var $showParser ShowSqlParser */
		$this->factorize($showParser);
		
		/* @var $useParser UseSqlParser */
		$this->factorize($useParser);
		
		/* @var $createParser CreateSqlParser */
		$this->factorize($createParser);
		
		/* @var $alterParser AlterSqlParser */
		$this->factorize($alterParser);
		
		/* @var $dropParser DropSqlParser */
		$this->factorize($dropParser);
		
		/* @var $setParser SetSqlParser */
		$this->factorize($setParser);
		
		/* @var $describeParser DescribeSqlParser */
		$this->factorize($describeParser);
		
		/* @var $beginParser BeginSqlParser */
	#	$this->factorize($beginParser);
		
		/* @var $endParser EndSqlParser */
	#	$this->factorize($endParser);
		
		/* @var $converter self */
		$converter = null;
		
		/* @var $jobEntity Job */
		$jobEntities = array();
		
	#	$tokens->seekIndex(-1);
		
		do{
			while($tokens->seekTokenText(';'));
		
			switch(true){
				
				### ETC
					
				case $parenthesisParser->canParseTokens($tokens, TokenIterator::CURRENT):
					$parenthesisJob = $parenthesisParser->convertSqlToJob($tokens, TokenIterator::CURRENT);
					$jobEntities[] = $parenthesisJob->getContain();
					break;
					
					### DATA
				
				case $selectParser->canParseTokens($tokens):
					$jobEntities[] = $selectParser->convertSqlToJob($tokens);
					break;
				
				case $insertParser->canParseTokens($tokens):
					$jobEntities[] = $insertParser->convertSqlToJob($tokens);
					break;
				
				case $updateParser->canParseTokens($tokens):
					$jobEntities[] = $updateParser->convertSqlToJob($tokens);
					break;
				
				case $deleteParser->canParseTokens($tokens):
					$jobEntities[] = $deleteParser->convertSqlToJob($tokens);
					break;
					
					### SCHEMA
					
				case $describeParser->canParseTokens($tokens):
					$jobEntities[] = $describeParser->convertSqlToJob($tokens);
					break;
					
				case $showParser->canParseTokens($tokens):
					$jobEntities[] = $showParser->convertSqlToJob($tokens);
					break;
					
				case $useParser->canParseTokens($tokens):
					$jobEntities[] = $useParser->convertSqlToJob($tokens);
					break;
			
				case $createParser->canParseTokens($tokens):
					$jobEntities[] = $createParser->convertSqlToJob($tokens);
					break;
					
				case $alterParser->canParseTokens($tokens):
					$jobEntities[] = $alterParser->convertSqlToJob($tokens);
					break;
					
				case $dropParser->canParseTokens($tokens):
					$jobEntities[] = $dropParser->convertSqlToJob($tokens);
					break;
					
					### CONFIGURATION
					
				case $setParser->canParseTokens($tokens):
					$jobEntities[] = $setParser->convertSqlToJob($tokens);
					break;
				
					### TRANSACTION
					
			#	case $tokens->seekTokenNum(SqlToken::T_BEGIN()):
			#		$converter = $this->factory("Begin");
			#		break;
					
			#	case $tokens->seekTokenNum(SqlToken::T_END()):
			#		$converter = $this->factory("End");
			#		break;
					
				case is_null($tokens->getExclusiveTokenNumber()) || $tokens->isAtEnd():
					break 2;
					
				default:
					$relevantToken = $tokens->getExclusiveTokenString();
					throw new MalformedSql("Invalid SQL-statement! (Cannot extract command: '{$relevantToken}')", $tokens);
			}
		
		}while($tokens->isTokenText(';'));
		
		if(!$tokens->isAtEnd() && $tokens->getExclusiveTokenIndex() !== $tokens->getIndex()){
			throw new MalformedSql("Overlapping unparsed SQL at the end of statement!", $tokens);
		}
		
		foreach($jobEntities as $job){
			$job->checkPlausibility();
		}
		
		return $jobEntities;
	}
}