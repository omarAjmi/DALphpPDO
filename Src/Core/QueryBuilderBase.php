<?php
namespace Src\Core;

use Src\Libs\DatabaseConnection;

class QueryBuilderBase
{

    /*
     * The query types.
     */
    private const SELECT = 0;
    private const DELETE = 1;
    private const UPDATE = 2;
    private const INSERT = 3;

    /*
     * The builder SQLStates.
     */
    private const STATE_DIRTY = 0;
    private const STATE_CLEAN = 1;

    /**
     * @var DatabaseConnexion
     */
    private $DBInstance;

    /**
     * @var array The array of SQL parts collected.
     */
    private $SQLBlocks = [
        'select' => [],
        'from' => [],
        'join' => [],
        'set' => [],
        'where' => null,
        'groupBy' => [],
        'having' => null,
        'orderBy' => [],
        'values' => [],
        'limit' => null
    ];

    /**
     * The complete SQL string for this query.
     *
     * @var string
     */
    private $SQLString;

    /**
     * The query parameters.
     *
     * @var array
     */
    private $SQLParams = [];

    /**
     * The type of query this is. Can be select, update or delete.
     *
     * @var integer
     */
    private $SQLType = self::SELECT;

    /**
     * The SQLState of the query object. Can be dirty or clean.
     *
     * @var integer
     */
    private $SQLState = self::STATE_CLEAN;

    /**
     * The counter of bound parameters used with {@see bindValue).
     *
     * @var integer
     */
    private $boundCounter = 0;

    /**
     * Constructor
     *
     * @param DatabaseConnection $conn
     */
    public function __construct(DatabaseConnection $conn)
    {
        try {
            $conn->getConnection();
            $this->DBInstance = $conn->PDOInstance;
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }
    /**
     * gets the Query Type
     *
     * @return int
     */
    public function getType()
    {
        return $this->SQLType;
    }

    /**
     * gets the Query Connection
     *
     * @return PDO
     */
    public function getConnection()
    {
        return $this->DBInstance->getConnection();
    }

    /**
     * gets the Query State
     *
     * @return int
     */
    public function getState()
    {
        return $this->SQLState;
    }

    /**
     * gets the Query String
     *
     * @return string
     */
    public function getQueryString()
    {
        if (!empty($this->SQLString) and $this->SQLState === $this->STATE_CLEAN) {
            return $this->SQLString;
        } else {
            switch ($this->SQLType) {
                case self::INSERT:
                    $sql = $this->getInsertQuery();
                    break;
                case self::DELETE:
                    $sql = $this->getDeleteQuery();
                    break;

                case self::UPDATE:
                    $sql = $this->getUpdateQuery();
                    break;

                case self::SELECT:
                default:
                    $sql = $this->getSelectQuery();
                    break;
            }
            $this->SQLString = $sql;
            return $this->SQLString;
        }
    }

    /**
     * sets Query Only Parameter
     *
     * @param string $key
     * @param mixed $value
     * @return QueryBuilderBase
     */
    public function setParameter($key, $value)
    {
        try {
            if (empty($key)) {
                throw new \Exception("paraeter key is empty");
            } else {
                $this->SQLParams[$key] = $value;
            }
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
        return $this;
    }

    /**
     * sets Query Parameters
     *
     * @param array $params
     * @return QueryBuilderBase
     */
    public function setParameters(array $params)
    {
        try {
            if (empty($params)) {
                throw new \Exception("parameters are empty");
            } else {
                $this->SQLParams = $params;
            }
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * gets Query Parameters
     *
     * @return QueryBuilderBase
     */
    public function getParameters()
    {
        return $this->SQLParams;
    }

    /**
     * gets Query specific Parameter
     *
     * @param string $key
     * @return QueryBuilderBase
     */
    public function getParameter(string $key)
    {
        isset($this->SQLParams[$key]) ? $this->SQLParams [$key] : null;
    }
}