<?php
/**
 * Copyright (C) 2015  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\PHPSQL\Column;

use Addiks\PHPSQL\Entity\ColumnData;
use Addiks\PHPSQL\Column\ColumnSchema;

interface ColumnDataFactoryInterface
{

    /**
     * Creates a new column-data-object.
     *
     * @param  integer             $columnSchema
     * @return ColumnDataInterface
     */
    public function createColumnData(
        $schemaId,
        $tableId,
        $columnId,
        ColumnSchema $columnPage
    );

}