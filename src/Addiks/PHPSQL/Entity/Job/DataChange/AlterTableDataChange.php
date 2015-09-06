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

namespace Addiks\PHPSQL\Entity\Job\DataChange;

use Addiks\PHPSQL\Entity\Job;
use Addiks\PHPSQL\Value\Enum\Sql\Alter\DataChange\AlterAttributeType;
use Addiks\PHPSQL\Entity\Job\Part\ColumnDefinition;
use Addiks\PHPSQL\Value\Specifier\ColumnSpecifier;

class AlterTableDataChange extends Job
{
    
    private $attribute;
    
    public function setAttribute(AlterAttributeType $attribute)
    {
        $this->attribute = $attribute;
    }
    
    public function getAttribute()
    {
        return $this->attribute;
    }
    
    private $value;
    
    public function setValue($value)
    {
        $this->value = $value;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    private $subject;
    
    /**
     * Subject can be either column or index
     * For table-actions, te table part is used.
     * @param Column $subject
     */
    public function setSubject(ColumnSpecifier $subject)
    {
        $this->subject = $subject;
    }
    
    /**
     * @param ColumnDefinition $subject
     */
    public function setSubjectColumnDefinition(ColumnDefinition $subject)
    {
        $this->subject = $subject;
    }
    
    /**
     * @param Index $subject
     */
    public function setSubjectIndex(Index $subject)
    {
        $this->subject = $subject;
    }
    
    public function getSubject()
    {
        return $this->subject;
    }
}
