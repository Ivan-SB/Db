<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2014 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @group      horde_db
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */
class Horde_Db_Adapter_Sqlite_ColumnDefinition extends Horde_Test_Case
{
    public $conn;

    public function testConstruct()
    {
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string'
        );
        $this->assertEquals('col_name', $col->getName());
        $this->assertEquals('string',   $col->getType());
    }

    public function testToSql()
    {
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string'
        );
        $this->assertEquals('"col_name" varchar(255)', $col->toSql());
    }

    public function testToSqlLimit()
    {
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string', 40
        );
        $this->assertEquals('"col_name" varchar(40)', $col->toSql());

        // set attribute instead
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string'
        );
        $col->setLimit(40);
        $this->assertEquals('"col_name" varchar(40)', $col->toSql());
    }

    public function testToSqlPrecisionScale()
    {
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'decimal', null, 5, 2
        );
        $this->assertEquals('"col_name" decimal(5, 2)', $col->toSql());

        // set attribute instead
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'decimal'
        );
        $col->setPrecision(5);
        $col->setScale(2);
        $this->assertEquals('"col_name" decimal(5, 2)', $col->toSql());
    }

    public function testToSqlNotNull()
    {
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string', null, null, null, null, null, false
        );
        $this->assertEquals('"col_name" varchar(255) NOT NULL', $col->toSql());

        // set attribute instead
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string'
        );
        $col->setNull(false);
        $this->assertEquals('"col_name" varchar(255) NOT NULL', $col->toSql());

        // set attribute to the default (true)
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string'
        );
        $col->setNull(true);
        $this->assertEquals('"col_name" varchar(255)', $col->toSql());
    }

    public function testToSqlDefault()
    {
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string', null, null, null, null, 'test', null
        );
        $this->assertEquals('"col_name" varchar(255) DEFAULT \'test\'', $col->toSql());

        // set attribute instead
        $col = new Horde_Db_Adapter_Base_ColumnDefinition(
            $this->conn, 'col_name', 'string'
        );
        $col->setDefault('test');
        $this->assertEquals('"col_name" varchar(255) DEFAULT \'test\'', $col->toSql());
    }
}
