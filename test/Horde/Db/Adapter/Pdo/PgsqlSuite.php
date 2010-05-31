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
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @group      horde_db
 * @category   Horde
 * @package    Horde_Db
 * @subpackage UnitTests
 */
class Horde_Db_Adapter_Pdo_PgsqlSuite extends PHPUnit_Framework_TestSuite
{
    public static $conn = null;

    public static function suite()
    {
        $suite = new self('Horde Framework - Horde_Db - PDO-PostgreSQL Adapter');

        $skip = true;
        if (extension_loaded('pdo') && in_array('pgsql', PDO::getAvailableDrivers())) {
            try {
                self::$conn = $suite->getConnection();
                $skip = false;
            } catch (Exception $e) {}
        }

        if ($skip) {
            $skipTest = new Horde_Db_Adapter_MissingTest('testMissingAdapter');
            $skipTest->adapter = 'PDO_PostgreSQL';
            $suite->addTest($skipTest);
            return $suite;
        }

        require_once dirname(__FILE__) . '/PgsqlTest.php';
        require_once dirname(__FILE__) . '/../Postgresql/ColumnTest.php';
        require_once dirname(__FILE__) . '/../Postgresql/ColumnDefinitionTest.php';
        require_once dirname(__FILE__) . '/../Postgresql/TableDefinitionTest.php';

        $suite->addTestSuite('Horde_Db_Adapter_Pdo_PgsqlTest');
        $suite->addTestSuite('Horde_Db_Adapter_Postgresql_ColumnTest');
        $suite->addTestSuite('Horde_Db_Adapter_Postgresql_ColumnDefinitionTest');
        $suite->addTestSuite('Horde_Db_Adapter_Postgresql_TableDefinitionTest');

        return $suite;
    }

    public function getConnection()
    {
        if (!is_null(self::$conn)) { return self::$conn; }

        $config = getenv('DB_ADAPTER_PDO_PGSQL_TEST_CONFIG');
        if ($config && !is_file($config)) {
            $config = array_merge(array('username' => '', 'password' => '', 'dbname' => 'test'), json_decode($config, true));
        } else {
            if (!$config) {
                $config = dirname(__FILE__) . '/../conf.php';
            }
            if (file_exists($config)) {
                require $config;
            }
            if (!isset($conf['db']['adapter']['pdo']['pgsql']['test'])) {
                throw new Exception('No configuration for pdo_pgsql test');
            }
            $config = $conf['db']['adapter']['pdo']['pgsql']['test'];
        }

        $conn = new Horde_Db_Adapter_Pdo_Pgsql($config);

        $cache = new Horde_Cache_Mock();
        $conn->setCache($cache);

        return array($conn, $cache);
    }

    protected function setUp()
    {
        $this->sharedFixture = $this;
    }

}
