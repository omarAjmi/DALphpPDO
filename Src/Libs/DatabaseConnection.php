<?php
/**
 * @author Omar A.Ajmi
 * @email devdevoops@gmail.com
 * @create date 2018-04-14 19:20:27
 * @modify date 2018-04-16 04:27:44
 * @desc [description]
 */
namespace App\Src\Libs;

require "DsnGenerator.php";
require __DIR__ . "/../Core/QueryBuilderBase.php";
//require __DIR__ . "/../Core/Exceptions/ExpressionBuilder.php";

use App\Src\Libs\DsnGenerator;
use App\Src\Libs\DatabaseConfiguration;
use App\Src\Core\Exceptions\ExpressionBuilder;
use App\Src\Core\QueryBuilderBase;
use \PDO;

class DatabaseConnection
{
    private $configuration;
    private $PDOInstance;
    private $PDODriver;

    /**
     * Constructor
     *
     * @param DatabaseConfiguration $config
     */
    public function __construct(DatabaseConfiguration $config)
    {
        if (!$config instanceof DatabaseConfiguration) {
            return false;
        }
        $this->configuration = $config->getParams();
        try {
            if (!class_exists('PDO', false))
                throw new \Exception("Php's PDO module is required to connect to the database");
            $this->PDODriver = $this->getName($this->configuration['driver']);
            if (!in_array($this->PDODriver, \PDO::getAvailableDrivers()))
                throw new \Exception('PDO Extension ' . $this->PDODriver . ' is not installed');
            $this->loadDatabase();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
        return $this->PDOInstance;
    }

    /**
     * @return null|string
     */
    private function buildDsn()
    {
        if (isset($this->configuration['dsn']))
            return $this->configuration['dsn'];
        $dsn = null;
        $generator = new DsnGenerator();
        switch ($this->PDODriver) :
            case 'sqlsrv':
            $dsn = $generator->getSqlsrvDNS($this);
            break;

        case 'dblib':
            $dsn = $generator->getDblibDNS($this);

            break;

        case 'sqlite':
            $dsn = $generator->getSqliteDNS($this);
            break;

        case 'pgsql':
            $dsn = $generator->getPgsqlDNS($this);
            break;

        case 'oci':
            $dsn = $generator->getOracleDNS($this);
            break;

        case 'ibm':
            $dsn = $generator->getIbmDNS($this);
            break;

        default:
            $dsn = $generator->getMysqlDNS($this);
            break;
        endswitch;
        return $dsn;
    }

    /**
     * @return mixed|\PDO
     */
    private function loadDatabase()
    {
        if (!$this->PDOInstance) {
            try {
                $dsn = $this->buildDsn();
                $Options = $this->resolveOptions();
                if (!$this->PDOInstance = new PDO($dsn, $this->configuration['user'], $this->configuration['password'], $Options['attr']))
                    throw new \PDOException('Connection to the database could not be established');
                if (count($Options['cmd']) > 0) {
                    foreach ($Options['cmd'] as $cmd)
                        $this->PDOInstance->exec($cmd);
                }
            } catch (\PDOException $e) {
                trigger_error($e->getMessage(), E_USER_ERROR);
            }
        }
        return $this->PDOInstance;
    }

    /**
     * @return mixed
     */
    private function resolveOptions()
    {
        $Options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ];
        $Command = [];
        $Params = $this->configuration;
        if ($this->getName($Params['driver']) === 'mysql') {
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND'))
                $Options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $Params['charset'];

            $Command[] = 'SET SQL_MODE=ANSI_QUOTES';
            $Options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
            $Options[PDO::MYSQL_ATTR_COMPRESS] = true;
        }

        if ($Params['fetchmode'] !== 'object')
            $Options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

        if (!$Params['persistent'])
            $Options[PDO::ATTR_PERSISTENT] = false;

        if (!$Params['prepare'])
            $Options[PDO::ATTR_EMULATE_PREPARES] = false;

        if (!isset($Options[PDO::MYSQL_ATTR_INIT_COMMAND]) and ($this->getName($Params['driver']) !== 'oci'))
            $Command[] = 'SET NAMES ' . $Params['charset'];

        if ($this->getName($Params['driver']) === 'sqlsrv')
            $Command[] = 'SET QUOTED_IDENTIFIER ON';

        return ['attr' => $Options, 'cmd' => $Command];
    }

    /**
     * @return mixed
     */
    public function ServerVersion()
    {
        if (!$this->PDOInstance instanceof PDO)
            return false;
        return $this->PDOInstance->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function getName($driver)
    {
        if (!$driver) $driver = 'mysql';
        $driver = strtolower($driver);
        switch ($driver) {
            case (strpos($driver, 'mssql')):
            case (strpos($driver, 'sqlserver')):
            case (strpos($driver, 'sqlsrv')):
                $driver = (strpos(PHP_OS, 'WIN') !== false) ? 'sqlsrv' : 'dblib';
                break;
            case (strpos($driver, 'sybase')):
                $driver = 'dblib';
                break;
            case (strpos($driver, 'pgsql')):
                $driver = 'pgsql';
                break;
            case (strpos($driver, 'sqlite')):
                $driver = 'sqlite';
                break;
            case (strpos($driver, 'ibm')):
            case (strpos($driver, 'db2')):
            case (strpos($driver, 'odbc')):
                $driver = 'ibm';
                break;
            case (strpos($driver, 'oracle')):
                $driver = 'oci';
                break;
            default:
                $driver = 'mysql';
                break;
        }
        return $driver;
    }

    /**
     * @return ExpressionBuilder
     */
    public function getExpressionBuilder()
    {
        return new ExpressionBuilder($this);
    }

    /**
     * @return QueryBuilderBase
     */
    protected function loadQueryBuilder()
    {
        return new QueryBuilderBase($this);
    }

    /**
     * @return PDO
     */
    public function getConnection()
    {
        try {
            if (!$this->PDOInstance instanceof \PDO)
                throw new \Exception('No Connection has been established with the database.');
        } catch (\Exception $a) {
            trigger_error($a->getMessage(), E_USER_ERROR);
        }
        return $this->PDOInstance;
    }

    /**
     * gets configurations
     *
     * @return array
     */
    public function getConfigurations()
    {
        return $this->configuration;
    }

    /**
     * @param $value
     * @return array|string
     */
    public function quote($value)
    {
        try {
            if (!$this->PDOInstance instanceof \PDO)
                throw new \Exception('No PDOInstance has been made with the connection.');
            if (is_array($value)) {
                $return = [];
                foreach ($value as $col => $_)
                    $return[$col] = call_user_func([$this, __METHOD__], $_);
                return $return;
            }
            if (is_numeric($value) && !is_string($value)) return (string)$value;
            if (is_bool($value)) return $value ? 1 : 0;
        } catch (\Exception $a) {
            trigger_error($a->getMessage(), E_USER_ERROR);
        }
        return $this->PDOInstance->quote($value);
    }
}
