<?php
/**
 *  DVelum project https://github.com/dvelum/dvelum
 *  Copyright (C) 2011-2017  Kirill Yegorov
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Dvelum\Orm\Object\Builder\General;
use Dvelum\Orm\Object\Builder;

class MySQL extends Builder\General
{
    protected $types = [
        'tinyint' => self::TYPE_INTEGER,
        'bit' => self::TYPE_INTEGER,
        'smallint' => self::TYPE_INTEGER,
        'mediumint' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINTEGER,
        'float' => self::TYPE_FLOATING,
        'double' => self::TYPE_FLOATING,
        'real' => self::TYPE_FLOATING,
        'decimal' => self::TYPE_DECIMAL,
        'numeric' => self::TYPE_DECIMAL,
        'tinytext' => self::TYPE_TEXT,
        'mediumtext' => self::TYPE_TEXT,
        'longtext' => self::TYPE_TEXT,
        'longblob' => self::TYPE_BLOB,
        'blob' => self::TYPE_BLOB,
        'text' => self::TYPE_TEXT,
        'varchar' => self::TYPE_VARCHAR,
        'string' => self::TYPE_VARCHAR,
        'char' => self::TYPE_CHAR,
        'datetime' => self::TYPE_DATE,
        'date' => self::TYPE_DATE,
        'time' => self::TYPE_TIME,
        'timestamp' => self::TYPE_BIGINTEGER,
        'enum' => self::TYPE_VARCHAR,
        'varbinary' => self::TYPE_BLOB
    ];
}
