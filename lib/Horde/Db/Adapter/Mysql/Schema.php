<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Mysql_Schema extends Horde_Db_Adapter_Base_Schema
{
    /*##########################################################################
    # Object factories
    ##########################################################################*/

    /**
     * Factory for Column objects
     */
    public function makeColumn($name, $default, $sqlType = null, $null = true)
    {
        return new Horde_Db_Adapter_Mysql_Column($name, $default, $sqlType, $null);
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * @return  string
     */
    public function quoteColumnName($name)
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * @return  string
     */
    public function quoteTableName($name)
    {
        return str_replace('.', '`.`', $this->quoteColumnName($name));
    }

    /**
     * @return  string
     */
    public function quoteTrue()
    {
        return '1';
    }

    /**
     * @return  string
     */
    public function quoteFalse()
    {
        return '0';
    }


    /*##########################################################################
    # Schema Statements
    ##########################################################################*/

    /**
     * The db column types for this adapter
     *
     * @return  array
     */
    public function nativeDatabaseTypes()
    {
        return array(
            'primaryKey' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'string'     => array('name' => 'varchar',  'limit' => 255),
            'text'       => array('name' => 'text',     'limit' => null),
            'integer'    => array('name' => 'int',      'limit' => 11),
            'float'      => array('name' => 'float',    'limit' => null),
            'decimal'    => array('name' => 'decimal',  'limit' => null),
            'datetime'   => array('name' => 'datetime', 'limit' => null),
            'timestamp'  => array('name' => 'datetime', 'limit' => null),
            'time'       => array('name' => 'time',     'limit' => null),
            'date'       => array('name' => 'date',     'limit' => null),
            'binary'     => array('name' => 'blob',     'limit' => null),
            'boolean'    => array('name' => 'tinyint',  'limit' => 1),
        );
    }

    /**
     * Create the given db
     *
     * @param   string  $name
     */
    public function createDatabase($name)
    {
        return $this->execute("CREATE DATABASE `$name`");
    }

    /**
     * Drop the given db
     *
     * @param   string  $name
     */
    public function dropDatabase($name)
    {
        return $this->execute("DROP DATABASE IF EXISTS `$name`");
    }

    /**
     * Get the name of the current db
     *
     * @return  string
     */
    public function currentDatabase()
    {
        return $this->selectValue('SELECT DATABASE() AS db');
    }

    /**
     * Returns the database character set that query results are in
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->showVariable('character_set_results');
    }

    /**
     * Set the client and result charset.
     *
     * @param string $charset  The character set to use for client queries and results.
     */
    public function setCharset($charset)
    {
        $charset = $this->_mysqlCharsetName($charset);
        $this->execute('SET NAMES ' . $this->quoteString($charset));
    }

    /**
     * Get the MySQL name of a given character set.
     *
     * @param string $charset
     * @return string MySQL-normalized charset.
     */
    public function _mysqlCharsetName($charset)
    {
        $charset = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $charset));
        if ($charset == 'iso88591') {
            $charset = 'latin1';
        }
        $validCharsets = $this->selectValues('SHOW CHARACTER SET');
        if (!in_array($charset, $validCharsets)) {
            throw new Horde_Db_Exception($charset . ' is not supported by MySQL');
        }

        return $charset;
    }

    /**
     * Returns the database collation strategy
     *
     * @return string
     */
    public function getCollation()
    {
        return $this->showVariable('collation_database');
    }

    /**
     * List of tables for the db
     *
     * @param   string  $name
     */
    public function tables($name=null)
    {
        return $this->selectValues('SHOW TABLES');
    }

    /**
     * Return a table's primary key
     */
    public function primaryKey($tableName, $name = null)
    {
        // Share the column cache with the columns() method
        $rows = @unserialize($this->_cache->get("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->selectAll('SHOW FIELDS FROM ' . $this->quoteTableName($tableName), $name);

            $this->_cache->set("tables/columns/$tableName", serialize($rows));
        }

        $pk = $this->makeIndex($tableName, 'PRIMARY', true, true, array());
        foreach ($rows as $row) {
            if ($row['Key'] == 'PRI') {
                $pk->columns[] = $row['Field'];
            }
        }

        return $pk;
    }

    /**
     * List of indexes for the given table
     *
     * @param   string  $tableName
     * @param   string  $name
     */
    public function indexes($tableName, $name=null)
    {
        $indexes = @unserialize($this->_cache->get("tables/indexes/$tableName"));

        if (!$indexes) {
            $indexes = array();
            $currentIndex = null;
            foreach ($this->select('SHOW KEYS FROM ' . $this->quoteTableName($tableName)) as $row) {
                if ($currentIndex != $row['Key_name']) {
                    if ($row['Key_name'] == 'PRIMARY') {
                        continue;
                    }
                    $currentIndex = $row['Key_name'];
                    $indexes[] = $this->makeIndex(
                        $tableName, $row['Key_name'], false, $row['Non_unique'] == '0', array());
                }
                $indexes[count($indexes) - 1]->columns[] = $row['Column_name'];
            }

            $this->_cache->set("tables/indexes/$tableName", serialize($indexes));
        }

        return $indexes;
    }

    /**
     * @param   string  $tableName
     * @param   string  $name
     */
    public function columns($tableName, $name=null)
    {
        $rows = @unserialize($this->_cache->get("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->selectAll('SHOW FIELDS FROM ' . $this->quoteTableName($tableName), $name);

            $this->_cache->set("tables/columns/$tableName", serialize($rows));
        }

        // create columns from rows
        $columns = array();
        foreach ($rows as $row) {
            $columns[$row['Field']] = $this->makeColumn(
                $row['Field'], $row['Default'], $row['Type'], $row['Null'] == 'YES');
        }

        return $columns;
    }

    /**
     * @param   string  $name
     * @param   array   $options
     */
    public function endTable($name, $options=array())
    {
        $opts = array('options' => 'ENGINE=InnoDB DEFAULT CHARSET=' . $this->getCharset());
        return parent::endTable($name, array_merge($opts, $options));
    }

    /**
     * @param   string  $name
     * @param   string  $newName
     */
    public function renameTable($name, $newName)
    {
        $this->_clearTableCache($name);

        return $this->execute('ALTER TABLE '.$this->quoteTableName($name).' RENAME '.$this->quoteTableName($newName));
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $default
     */
    public function changeColumnDefault($tableName, $columnName, $default)
    {
        $this->_clearTableCache($tableName);

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        $sql = "SHOW COLUMNS FROM $quotedTableName LIKE ".$this->quoteString($columnName);
        $res = $this->selectOne($sql);
        $currentType = $res['Type'];

        $default = $this->quote($default);
        $sql = "ALTER TABLE $quotedTableName CHANGE $quotedColumnName $quotedColumnName
                $currentType DEFAULT $default";
        return $this->execute($sql);
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $type
     * @param   array   $options
     */
    public function changeColumn($tableName, $columnName, $type, $options=array())
    {
        $this->_clearTableCache($tableName);

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        if (!array_key_exists('default', $options)) {
            $row = $this->selectOne("SHOW COLUMNS FROM $quotedTableName LIKE ".$this->quoteString($columnName));
            $options['default'] = $row['Default'];
        }

        $limit     = !empty($options['limit'])     ? $options['limit']     : null;
        $precision = !empty($options['precision']) ? $options['precision'] : null;
        $scale     = !empty($options['scale'])     ? $options['scale']     : null;
        $unsigned  = !empty($options['unsigned'])  ? $options['unsigned']  : null;

        $typeSql = $this->typeToSql($type, $limit, $precision, $scale, $unsigned);

        $sql = "ALTER TABLE $quotedTableName CHANGE $quotedColumnName $quotedColumnName $typeSql";
        $sql = $this->addColumnOptions($sql, $options);
        $this->execute($sql);
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $newColumnName
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        $this->_clearTableCache($tableName);

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        $sql = "SHOW COLUMNS FROM $quotedTableName LIKE ".$this->quoteString($columnName);
        $res = $this->selectOne($sql);
        $currentType = $res["Type"];

        $sql = "ALTER TABLE $quotedTableName CHANGE ".
                $quotedColumnName.' '.
                $this->quoteColumnName($newColumnName)." ".
                $currentType;
        return $this->execute($sql);
    }

    /**
     * SHOW VARIABLES LIKE 'name'
     *
     * @param   string  $name
     * @return  string
     */
    public function showVariable($name)
    {
        $value = $this->selectOne('SHOW VARIABLES LIKE '.$this->quoteString($name));
        if ($value[0] == $name) {
            return $value[1];
        } else {
            throw new Horde_Db_Exception($name . ' is not a recognized variable');
        }
    }

    /**
     */
    public function caseSensitiveEqualityOperator()
    {
        return '= BINARY';
    }

    /**
     */
    public function limitedUpdateConditions($whereSql, $quotedTableName, $quotedPrimaryKey)
    {
        return $whereSql;
    }

    /**
     * The sql for this column type
     *
     * @param   string  $type
     * @param   string  $limit
     */
    public function typeToSql($type, $limit = null, $precision = null, $scale = null, $unsigned = null)
    {
        // If there is no explicit limit, adjust $nativeLimit for unsigned
        // integers.
        if ($type == 'integer' && !empty($unsigned) && empty($limit)) {
            $natives = $this->nativeDatabaseTypes();
            $native = isset($natives[$type]) ? $natives[$type] : null;
            if (empty($native)) { return $type; }

            $nativeLimit = is_array($native) ? $native['limit'] : null;
            if (is_integer($nativeLimit)) {
                $limit = $nativeLimit - 1;
            }
        }

        $sql = parent::typeToSql($type, $limit, $precision, $scale, $unsigned);

        if (!empty($unsigned)) {
            $sql .= ' UNSIGNED';
        }

        return $sql;
    }

    /**
     * Add additional column options.
     *
     * @param   string  $sql
     * @param   array   $options
     * @return  string
     */
    public function addColumnOptions($sql, $options)
    {
        $sql = parent::addColumnOptions($sql, $options);
        if (isset($options['after'])) {
            $sql .= " AFTER ".$this->quoteColumnName($options['after']);
        }
        if (!empty($options['autoincrement'])) {
            $sql .= ' AUTO_INCREMENT';
        }
        return $sql;
    }

}
