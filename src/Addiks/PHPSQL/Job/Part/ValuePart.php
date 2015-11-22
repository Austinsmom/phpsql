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

namespace Addiks\PHPSQL\Job\Part;

use Addiks\PHPSQL\Job\Part;
use Addiks\PHPSQL\Value\Specifier\ColumnSpecifier;
use Addiks\PHPSQL\Job\Part\FunctionJob;
use Addiks\PHPSQL\Job\Part\ConditionJob;

class ValuePart extends Part
{
    
    private $chain = array();
    
    public function addChainValue($value)
    {
        $this->chain[] = $value;
    }
    
    public function getChainValues()
    {
        return $this->chain;
    }
    
    public function generateAlias()
    {
        
        if (is_null($this->getAlias())) {
            $this->setAlias("");
            
            foreach ($this->chain as $chainValue) {
                switch(true){
            
                    case $chainValue instanceof ColumnSpecifier:
                        $this->setAlias($this->getAlias() . (string)$chainValue);
                        break;
                        
                    case $chainValue instanceof FunctionJob:
                        $this->setAlias($this->getAlias() . $chainValue->getName() . "()");
                        break;
            
                    case method_exists($chainValue, 'getAlias'):
                        $this->setAlias($this->getAlias() . $chainValue->getAlias());
                        break;
                }
            }
            
            if (strlen(parent::getAlias())<=0) {
                $this->setAlias($this->getAlias() . "VALUE#".uniqid());
            }
            
        }
        return $this->getAlias();
    }

    public function resolve()
    {
        $lastIndex = count($this->chain)-1;

        do {
            $this->chain = array_values($this->chain);

            foreach ($this->chain as $index => $chainValue) {
                if ($chainValue instanceof ConditionJob) {
                    if (!$chainValue->hasLastParameter() && $index < $lastIndex) {
                        $parameter = $this->chain[$index+1];
                        unset($this->chain[$index+1]);
                        $chainValue->setLastParameter($parameter);
                        continue 2;
                    }
                    if (!$chainValue->hasFirstParameter() && $index > 0) {
                        $parameter = $this->chain[$index-1];
                        unset($this->chain[$index-1]);
                        $chainValue->setFirstParameter($parameter);
                        continue 2;
                    }
                }

                if ($chainValue instanceof Part) {
                    $chainValue->resolve();
                }
            }

            break;
        } while (true);

        parent::resolve();
    }

    public function __toString()
    {
        $string = "";

        foreach ($this->chain as $chainValue) {
            $string .= (string)$chainValue;
        }

        return $string;
    }
}
