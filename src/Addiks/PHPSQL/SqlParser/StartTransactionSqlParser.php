<?php
/**
 * Copyright (C) 2015  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\PHPSQL\SqlParser;
use Addiks\PHPSQL\Value\Enum\Sql\SqlToken;
use Addiks\PHPSQL\Iterators\TokenIterator;
use Addiks\PHPSQL\Iterators\SQLTokenIterator;
use Addiks\PHPSQL\Job\Statement\StartTransactionStatement;
use Addiks\PHPSQL\Exception\MalformedSqlException;

class StartTransactionSqlParser extends SqlParser
{

    public function canParseTokens(SQLTokenIterator $tokens)
    {
        return is_int($tokens->isTokenNum(SqlToken::T_START(), TokenIterator::CURRENT))
            && is_int($tokens->isTokenNum(SqlToken::T_TRANSACTION(), TokenIterator::NEXT, [SqlToken::T_START()]));
    }

    public function convertSqlToJob(SQLTokenIterator $tokens)
    {
        $statement = null;

        if ($tokens->seekTokenNum(SqlToken::T_TRANSACTION(), SQLTokenIterator::NEXT, [SqlToken::T_START()])) {
            $statement = new StartTransactionStatement();

        } else {
            throw new MalformedSqlException(
                "Tried to parse START TRANSACTION statement when token-iterator does not point to T_START!",
                $tokens
            );
        }

        return $statement;
    }

}
