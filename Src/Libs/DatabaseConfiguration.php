<?php
namespace App\Src\Libs;

require __DIR__."/../Core/Config/ConfigManager.php";
require __DIR__. "/../Core/Config/OptionResolver.php";


use App\Src\Core\Config\ConfigManager;
use App\Src\Core\Config\OptionResolver;

class DatabaseConfiguration
{
    const DEFAULT_PARAMETERS = [
            'driver' => 'mysql',
            'charset' => 'utf8',
            'host' => 'localhost',
            'dbname' => null,
            'port' => 3306,
            'password' => null,
            'user' => null,
            'prefix' => '',
            'persistent' => true,
            'fetchmode' => 'object',
            'prepare' => true
    ];
    private $poolName = 'mysql';
    private $dbConfig;

    public function __construct(string $poolName)
    {
        $this->poolName = $poolName;
        //$this->dbConfig = new dataCollector();
        $this->load();
        return $this;
    }
    /**
     * @param $value
     * @return array|string
     */
    public function quote($value)
    {
        try {
            if (!$this->PDOInstance instanceof \PDO)
                throw new \Exception('Aucune PDOInstanceion n\'a été établie avec la base de donnée.');
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

    public function getParams()
    {
        return $this->dbConfig->get($this->poolName, []);
    }

    private function load()
    {
        try {

            $DS = DIRECTORY_SEPARATOR;
            $configFilePath = dirname(__DIR__) . '/Settings.php';
            if (!$setup = new ConfigManager($configFilePath) ) {
                throw new \Exception("Configuration file not found!");
            }
            
            $dbParams = array_change_key_case($setup->get($this->poolName), CASE_LOWER);
            $dbParams['port'] = $this->setPort($dbParams['driver'], $dbParams['port'] ? : null);
            $dbParams['prepare'] = isset($dbParams['prepare']) ? true : false;
            $dbParams['persistent'] = isset($dbParams['persistent']) ? true : false;
            
            $optionResolver = new OptionResolver();
            $optionResolver->setDefaults($this->getDefaults());
            $optionResolver->setRequired(['dbname', 'driver', 'host', 'password', 'user']);
            $optionResolver->setAllowedValues('fetchmode', ['array', 'object']);
            $optionResolver->addAllowedValues('driver', ['mysql', 'pgsql', 'sqlite', 'oracle', 'sqlsrv', 'mssql', 'sqlserver', 'ibm', 'db2', 'sybase', 'odbc']);
            $optionResolver->setAllowedTypes('port', 'integer');
            $optionResolver->setAllowedTypes('persistent', 'bool');
            $optionResolver->setAllowedTypes('prepare', 'bool');
            $dbParams = $optionResolver->resolve($dbParams);
            $this->dbConfig->add([$this->dbPoolName => $dbParams]);
            return $dbParams;
        } catch(\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }
    /**
     * set the connection port
     *
     * @param string $driver
     * @param int|null $port
     * @return int|null
     */
    private function setPort(string $driver, $port = null)
    {
        if (!$port or !is_int($port * 1)) {
            switch ($driver) :
                case 'oracle':
                $port = 1521;
                break;
            case 'pgsql':
                $port = 5432;
                break;
            case 'ibm':
            case 'db2':
            case 'odbc':
                $port = 50000;
                break;
            case 'sqlsrv':
            case 'mssql':
            case 'sqlserver':
            case 'sybase':
                $port = 1433;
                break;
            default:
                $port = 3306;
            endswitch;
        }
        return $port;
    }

    /**
     * gets default parameters
     *
     * @return array
     */
    private function getDefaults()
    {
        return self::DEFAULT_PARAMETERS;
    }
}
