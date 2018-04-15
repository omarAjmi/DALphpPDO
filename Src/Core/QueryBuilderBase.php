<?php
namespace Src\Core;

class QueryBuilderBase
{

    /*
     * The query types.
     */
    const SELECT = 0;
    const DELETE = 1;
    const UPDATE = 2;
    const INSERT = 3;

    /*
     * The builder SQLStates.
     */
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /**
     * @var DatabaseConnection
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
        if (!empty($this->SQLString) and $this->SQLState === self::STATE_CLEAN) {
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
                throw new \Exception("parameter key is empty");
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

    /**
     * adds an SQL block to Query
     *
     * @param string $sqlPartName
     * @param [type] $sqlPart
     * @param boolean $append
     * @return QueryBuilderBase
     */
    private function addSQLBlock(string $sqlPartName, mixed $sqlPart, bool $append = false)
    {
        $isArray = is_array($sqlPart);
        $isMultiple = is_array($this->SQLBlocks[$sqlPartName]);
        if ($isMultiple && !$isArray)
            $sqlPart = array($sqlPart);
        $this->SQLState = self::STATE_DIRTY;

        if ($append) {
            $this->appendSQLBlock($sqlPartName, $sqlPart, $isArray, $isMultiple);
            return $this;
        } else {
            $this->SQLBlocks[$sqlPartName] = $sqlPart;
        }
        return $this;
    }

    /**
     * appends SQL block to the alreay existing SQL Block
     *
     * @param string $sqlPartName
     * @param mixed $sqlPart
     * @param boolean $isArray
     * @param boolean $isMultiple
     * @return void
     */
    private function appendSQLBlock(string $sqlPartName, mixed $sqlPart, bool $isArray, bool $isMultiple)
    {
        if ($sqlPartName == "orderBy" || $sqlPartName == "groupBy" || $sqlPartName == "select" || $sqlPartName == "set") {
            foreach ($sqlPart as $part) {
                $this->SQLBlocks[$sqlPartName][] = $part;
            }
        } elseif ($isArray && is_array($sqlPart[key($sqlPart)])) {
            $key = key($sqlPart);
            $this->SQLBlocks[$sqlPartName][$key][] = $sqlPart[$key];
        } elseif ($isMultiple) {
            $this->SQLBlocks[$sqlPartName][] = $sqlPart;
        } else {
            $this->SQLBlocks[$sqlPartName] = $sqlPart;
        }
    }

    /**
     * specifies the returned values from the Query,
     * replaces any old values, if any.
     *
     * @param mixed $select
     * @return QueryBuilderBase
     */
    public function select(mixed $select = null)
    {
        $this->SQLType = self::SELECT;
        if (empty($select)){
            return $this;
        }
        $selects = is_array($select) ? $select : [$select] ;
        $this->addSQLBlock('select', $selects, false);
        return $this;
    }

    /**
     * adds a returned value from the query to the allready existing values, if any
     *
     * @param mixed $select
     * @return QueryBuilderBase
     */
    public function addSelect(mixed $select = null)
    {
        $this->SQLType = self::SELECT;
        if (empty($select)) {
            return $this;
        }
        $selects = is_array($select) ? $select : [$select];
        $this->addSQLBlock('select', $selects, true);
        return $this;
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain table.
     *
     * @example $qb = $conn->QueryBuilder()
     *             ->delete('users', 'u')
     *             ->where('u.id = :user_id');
     *             ->setParameter(':user_id', 1);
     *
     * @param null $delete
     * @param null $alias
     * @return $this|QueryBuilderBase
     */
    public function delete($delete = null, $alias = null)
    {
        $this->SQLType = self::DELETE;

        if (!$delete) {
            return $this;
        }

        return $this->addSQLBlock('from', array(
            'table' => $delete,
            'alias' => $alias
        ));
    }

    /**
     *
     * Turns the query being built into a bulk update query that ranges over
     * a certain table
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->update('users', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     * </code>
     *
     * @param null $update
     * @param null $alias
     * @return $this|QueryBuilderBase
     */
    public function update($update = null, $alias = null)
    {
        $this->SQLType = self::UPDATE;

        if (!$update) {
            return $this;
        }

        return $this->addSQLBlock('from', array(
            'table' => $update,
            'alias' => $alias
        ));
    }

    /**
     * Turns the query being built into an insert query that inserts into
     * a certain table
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?',
     *                 'password' => '?'
     *             )
     *         );
     * </code>
     *
     * @param null $insert
     * @return $this|QueryBuilderBase
     */
    public function insert($insert = null)
    {
        $this->SQLType = self::INSERT;

        if (!$insert) {
            return $this;
        }

        return $this->addSQLBlock('from', array(
            'table' => $insert
        ));
    }

    /**
     * Creates and adds a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.id')
     *         ->from('users', 'u')
     * </code>
     *
     * @param string      $from  The table.
     * @param string|null $alias The alias of the table.
     *
     * @return QueryBuilderBase
     */
    public function from($from, $alias = null)
    {
        return $this->addSQLBlock('from', array(
            'table' => $from,
            'alias' => $alias
        ), true);
    }

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->join('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param $fromAlias
     * @param $join
     * @param $alias
     * @param null $condition
     * @return QueryBuilderBase
     */
    public function join($fromAlias, $join, $alias, $condition = null)
    {
        return $this->innerJoin($fromAlias, $join, $alias, $condition);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->crossJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function crossJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'cross',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->fullJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function fullJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'full',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->fullOuterJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function fullOuterJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'full outer',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->innerJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param $fromAlias
     * @param $join
     * @param $alias
     * @param null $condition
     * @return QueryBuilderBase
     */
    public function innerJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'inner',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }

    /**
     * Creates and adds a left join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function leftJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'left',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftLinearJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function leftLinearJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'left linear',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftOuterJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function leftOuterJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'left outer',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->linearJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function linearJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'linear',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->naturaloin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function naturalJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'natural',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }

    /**
     * Creates and adds a left join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->outerJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function outerJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'outer',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a right join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function rightJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'right',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightLinearJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function rightLinearJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'right linear',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightOuterJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function rightOuterJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'right outer',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->unionJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilderBase
     */
    public function unionJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->addSQLBlock('join', array(
            $fromAlias => array(
                'joinType' => 'union',
                'joinTable' => $join,
                'joinAlias' => $alias,
                'joinCondition' => $condition
            )
        ), true);
    }
    /**
     * Sets a new value for a column in a bulk update query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->update('users', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     * </code>
     *
     * @param string $key   The column to set.
     * @param string $value The value, expression, placeholder, etc.
     *
     * @return QueryBuilderBase
     */
    public function set($key, $value = null)
    {
        if (!$key) return $this;
        if (is_array($key)) {
            foreach ($key as $k => $v)
                call_user_func_array([$this, __METHOD__], [$k, $v]);
        }
        return $this->addSQLBlock('set', $key . ' = ' . $value, true);
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->where('u.id = ?');
     *
     *     // You can optionally programatically build and/or expressions
     *     $qb = $conn->QueryBuilder();
     *
     *     $or = $qb->expr()->orx();
     *     $or->addSQLBlock($qb->expr()->eq('u.id', 1));
     *     $or->addSQLBlock($qb->expr()->eq('u.id', 2));
     *
     *     $qb->update('users', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where($or);
     * </code>
     *
     * @param mixed $predicates The restriction predicates.
     *
     * @return QueryBuilderBase
     */
    public function where($predicates)
    {
        if (!(func_num_args() == 1 && $predicates instanceof CompositeExpression)) {
            $predicates = new CompositeExpression(CompositeExpression::TYPE_AND, func_get_args());
        }

        return $this->addSQLBlock('where', $predicates);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.username LIKE ?')
     *         ->andWhere('u.is_active = 1');
     * </code>
     *
     * @param mixed $where The query restrictions.
     *
     * @param $where
     * @return QueryBuilderBase
     */
    public function andWhere($where)
    {
        $args = func_get_args();
        $where = $this->getQueryBlock('where');

        if ($where instanceof CompositeExpression && $where->getType() === CompositeExpression::TYPE_AND) {
            $where->addMultiple($args);
        } else {
            array_unshift($args, $where);
            $where = new CompositeExpression(CompositeExpression::TYPE_AND, $args);
        }

        return $this->addSQLBlock('where', $where, true);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $em->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->where('u.id = 1')
     *         ->orWhere('u.id = 2');
     * </code>
     *
     * @param mixed $where The WHERE SQLStatement.
     *
     * @return QueryBuilderBase
     *
     * @see where()
     */
    public function orWhere($where)
    {
        $args = func_get_args();
        $where = $this->getQueryBlock('where');

        if ($where instanceof CompositeExpression && $where->getType() === CompositeExpression::TYPE_OR) {
            $where->addMultiple($args);
        } else {
            array_unshift($args, $where);
            $where = new CompositeExpression(CompositeExpression::TYPE_OR, $args);
        }
        return $this->addSQLBlock('where', $where, true);
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.id');
     * </code>
     *
     * @param mixed $groupBy The grouping expression.
     *
     * @return QueryBuilderBase
     */
    public function groupBy($groupBy)
    {
        if (empty($groupBy)) {
            return $this;
        }

        $groupBy = is_array($groupBy) ? $groupBy : func_get_args();

        return $this->addSQLBlock('groupBy', $groupBy, false);
    }


    /**
     * Adds a grouping expression to the query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.lastLogin');
     *         ->addGroupBy('u.createdAt')
     * </code>
     *
     * @param mixed $groupBy The grouping expression.
     *
     * @return QueryBuilderBase
     */
    public function addGroupBy($groupBy)
    {
        if (empty($groupBy)) {
            return $this;
        }

        $groupBy = is_array($groupBy) ? $groupBy : func_get_args();

        return $this->addSQLBlock('groupBy', $groupBy, true);
    }

    /**
     * Sets a value for a column in an insert query.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?'
     *             )
     *         )
     *         ->setValue('password', '?');
     * </code>
     *
     * @param $column
     * @param $value
     * @return $this
     */
    public function setValue($column, $value)
    {
        $this->SQLBlocks['values'][$column] = $value;

        return $this;
    }

    /**
     * Specifies values for an insert query indexed by column names.
     * Replaces any previous values, if any.
     *
     * <code>
     *     $qb = $conn->QueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?',
     *                 'password' => '?'
     *             )
     *         );
     * </code>
     *
     * @param array $values
     * @return QueryBuilderBase
     */
    public function values(array $values)
    {
        return $this->addSQLBlock('values', $values);
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param mixed $having The restriction over the groups.
     *
     * @return QueryBuilderBase
     */
    public function having($having)
    {
        if (!(func_num_args() == 1 && $having instanceof CompositeExpression)) {
            $having = new CompositeExpression(CompositeExpression::TYPE_AND, func_get_args());
        }

        return $this->addSQLBlock('having', $having);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to append.
     *
     * @return QueryBuilderBase
     */
    public function andHaving($having)
    {
        $args = func_get_args();
        $having = $this->getQueryBlock('having');

        if ($having instanceof CompositeExpression && $having->getType() === CompositeExpression::TYPE_AND) {
            $having->addMultiple($args);
        } else {
            array_unshift($args, $having);
            $having = new CompositeExpression(CompositeExpression::TYPE_AND, $args);
        }

        return $this->addSQLBlock('having', $having);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to add.
     *
     * @return QueryBuilderBase
     */
    public function orHaving($having)
    {
        $args = func_get_args();
        $having = $this->getQueryBlock('having');

        if ($having instanceof CompositeExpression && $having->getType() === CompositeExpression::TYPE_OR) {
            $having->addMultiple($args);
        } else {
            array_unshift($args, $having);
            $having = new CompositeExpression(CompositeExpression::TYPE_OR, $args);
        }

        return $this->addSQLBlock('having', $having);
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return QueryBuilderBase
     */
    public function orderBy($sort, $order = null)
    {
        return $this->addSQLBlock('orderBy', $sort . ' ' . (!$order ? 'ASC' : $order), false);
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return QueryBuilderBase
     */
    public function addOrderBy($sort, $order = null)
    {
        return $this->addSQLBlock('orderBy', $sort . ' ' . (!$order ? 'ASC' : $order), true);
    }

    /**
     * Gets a query part by its name.
     *
     * @param string $BlockName
     *
     * @return mixed
     */
    public function getQueryBlock($BlockName)
    {
        return $this->SQLBlocks[$BlockName];
    }

    /**
     * Gets all query parts.
     *
     * @return array
     */
    public function getQueryBlocks()
    {
        return $this->SQLBlocks;
    }

    /**
     * Resets SQL parts.
     *
     * @return QueryBuilderBase
     */
    private function resetSQLBlocks()
    {
        $BlockNames = array_keys($this->SQLBlocks);
        foreach ($BlockNames as $BlockName)
            $this->resetSQLBlock($BlockName);
        return $this;
    }

    /**
     * Resets a single SQL part.
     *
     * @param string $BlockName
     *
     * @return QueryBuilderBase
     */
    public function resetSQLBlock($BlockName)
    {
        $this->SQLBlocks[$BlockName] = is_array($this->SQLBlocks[$BlockName])
            ? [] : null;

        $this->SQLState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * @return string
     */
    private function getSelectQuery()
    {
        if (!$this->SQLBlocks['select']) {
            $table = current($this->SQLBlocks['from']);
            $this->SQLBlocks['select'][] = !isset($table['alias']) ? '* ' : $table['alias'] . '.* ';
        }
        $query = 'SELECT ' . implode(', ', $this->SQLBlocks['select']) . ' FROM ';

        $query .= implode(', ', $this->getFromClauses())
            . ($this->SQLBlocks['where'] !== null ? ' WHERE ' . ((string)$this->SQLBlocks['where']) : '')
            . ($this->SQLBlocks['groupBy'] ? ' GROUP BY ' . implode(', ', $this->SQLBlocks['groupBy']) : '')
            . ($this->SQLBlocks['having'] !== null ? ' HAVING ' . ((string)$this->SQLBlocks['having']) : '')
            . ($this->SQLBlocks['orderBy'] ? ' ORDER BY ' . implode(', ', $this->SQLBlocks['orderBy']) : '');

        if ($this->isLimitQuery())
            $query .= ' LIMIT ' . $this->SQLBlocks['limit'];

        return $query;
    }

    /**
     * @param $limit
     * @param int $offset
     * @return QueryBuilderBase
     */
    public function limit($limit, $offset = 0)
    {
        if (!$limit) return $this;
        $limit = sprintf('%d,%d', (int)$offset, (int)$limit);
        if ($this->SQLType !== self::SELECT)
            $limit = sprintf('%d', (int)$limit);
        return $this->addSQLBlock('limit', $limit);
    }
    /**
     * @return string[]
     */
    private function getFromClauses()
    {
        $fromClauses = [];
        $knownAliases = [];

        // Loop through all FROM clauses
        foreach ($this->SQLBlocks['from'] as $from) {
            if ($from['alias'] === null) {
                $tableSql = $from['table'];
                $tableReference = $from['table'];
            } else {
                $tableSql = $from['table'] . ' ' . $from['alias'];
                $tableReference = $from['alias'];
            }

            $knownAliases[$tableReference] = true;

            $fromClauses[$tableReference] = $tableSql . $this->getSQLForJoins($tableReference, $knownAliases);
        }

        $this->verifyAllAliasesAreKnown($knownAliases);

        return $fromClauses;
    }

    /**
     * @param array $knownAliases
     * @throws QueryException
     */
    private function verifyAllAliasesAreKnown(array $knownAliases)
    {
        foreach ($this->SQLBlocks['join'] as $fromAlias => $joins) {
            if (!isset($knownAliases[$fromAlias])) {
                throw QueryException::unknownAlias($fromAlias, array_keys($knownAliases));
            }
        }
    }

    /**
     * @return bool
     */
    private function isLimitQuery()
    {
        return count($this->SQLBlocks['limit']) > 0;
    }

    /**
     * Converts this instance into an INSERT string in SQL.
     *
     * @return string
     */
    private function getInsertQuery()
    {
        return 'INSERT' . ' INTO ' . $this->SQLBlocks['from']['table'] .
            ' (' . implode(', ', array_keys($this->SQLBlocks['values'])) . ')' .
            ' VALUES(' . implode(', ', $this->SQLBlocks['values']) . ')';
    }

    /**
     * Converts this instance into an UPDATE string in SQL.
     *
     * @return string
     */
    private function getUpdateQuery()
    {
        $table = $this->SQLBlocks['from']['table'] . ($this->SQLBlocks['from']['alias'] ? ' ' . $this->SQLBlocks['from']['alias'] : '');
        $query = 'UPDATE ' . $table
            . ' SET ' . implode(", ", $this->SQLBlocks['set'])
            . ($this->SQLBlocks['where'] !== null ? ' WHERE ' . ((string)$this->SQLBlocks['where']) : '');
        if ($this->isLimitQuery())
            $query .= ' LIMIT ' . $this->SQLBlocks['limit'];

        return $query;
    }

    /**
     * Converts this instance into a DELETE string in SQL.
     *
     * @return string
     */
    private function getDeleteQuery()
    {
        $table = $this->SQLBlocks['from']['table'] . ($this->SQLBlocks['from']['alias'] ? ' ' . $this->SQLBlocks['from']['alias'] : '');
        $query = 'DELETE';
        $query .= ' FROM ' . $table . ($this->SQLBlocks['where'] !== null ? ' WHERE ' . ((string)$this->SQLBlocks['where']) : '');
        if ($this->isLimitQuery())
            $query .= ' LIMIT ' . $this->SQLBlocks['limit'];
        return $query;
    }

    /**
     * Gets a string representation of this QueryBuilder which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this QueryBuilder.
     */
    public function __toString()
    {
        return $this->getQuery();
    }

    /**
     * Creates a new named parameter and bind the value $value to it.
     *
     * This method provides a shortcut for PDOStatement::bindValue
     * when using prepared SQLStatements.
     *
     * The parameter $value specifies the value that you want to bind. If
     * $placeholder is not provided bindValue() will automatically create a
     * placeholder for you. An automatic placeholder will be of the name
     * ':dcValue1', ':dcValue2' etc.
     *
     * For more information see {@link http://php.net/pdoSQLStatement-bindparam}
     *
     * Example:
     * <code>
     * $value = 2;
     * $q->eq( 'id', $q->bindValue( $value ) );
     * $stmt = $q->execute(); // executed with 'id = 2'
     * </code>
     *
     * @license New BSD License
     * @link http://www.zetacomponents.org
     *
     * @param mixed  $value
     * @param string $placeHolder The name to bind with. The string must start with a colon ':'.
     * @return string the placeholder name used.
     */
    public function createNamedParameter($value, $placeHolder = null)
    {
        if ($placeHolder === null) {
            $this->boundCounter++;
            $placeHolder = ":dcValue" . $this->boundCounter;
        }
        $this->setParameter(substr($placeHolder, 1), $value);

        return $placeHolder;
    }

    /**
     * Creates a new positional parameter and bind the given value to it.
     *
     * Attention: If you are using positional parameters with the query builder you have
     * to be very careful to bind all parameters in the order they appear in the SQL
     * SQLStatement , otherwise they get bound in the wrong order which can lead to serious
     * bugs in your code.
     *
     * Example:
     * <code>
     *  $qb = $conn->QueryBuilder();
     *  $qb->select('u.*')
     *     ->from('users', 'u')
     *     ->where('u.username = ' . $qb->createPositionalParameter('Foo', PDO::PARAM_STR))
     *     ->orWhere('u.username = ' . $qb->createPositionalParameter('Bar', PDO::PARAM_STR))
     * </code>
     *
     * @param mixed   $value
     *
     * @return string
     */
    public function createPositionalParameter($value)
    {
        $this->boundCounter++;
        $this->setParameter($this->boundCounter, $value);

        return "?";
    }

    /**
     * @param $fromAlias
     * @param array $knownAliases
     * @return string
     * @throws QueryException
     */
    private function getSQLForJoins($fromAlias, array &$knownAliases)
    {
        $sql = '';

        if (isset($this->SQLBlocks['join'][$fromAlias])) {
            foreach ($this->SQLBlocks['join'][$fromAlias] as $join) {
                if (array_key_exists($join['joinAlias'], $knownAliases)) {
                    throw QueryException::nonUniqueAlias($join['joinAlias'], array_keys($knownAliases));
                }
                $sql .= ' ' . strtoupper($join['joinType'])
                    . ' JOIN ' . $join['joinTable'] . ' ' . $join['joinAlias']
                    . ' ON ' . ((string)$join['joinCondition']);
                $knownAliases[$join['joinAlias']] = true;
            }

            foreach ($this->SQLBlocks['join'][$fromAlias] as $join) {
                $sql .= $this->getSQLForJoins($join['joinAlias'], $knownAliases);
            }
        }

        return $sql;
    }

    /**
     * Deep clone of all expression objects in the SQL parts.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->SQLBlocks as $part => $elements) {
            if (is_array($this->SQLBlocks[$part])) {
                foreach ($this->SQLBlocks[$part] as $idx => $element) {
                    if (is_object($element)) {
                        $this->SQLBlocks[$part][$idx] = clone $element;
                    }
                }
            } elseif (is_object($elements)) {
                $this->SQLBlocks[$part] = clone $elements;
            }
        }

        foreach ($this->SQLParams as $name => $param) {
            if (is_object($param)) {
                $this->SQLParams[$name] = clone $param;
            }
        }
    }

    /**
     * @param $value
     * @return array|string
     */
    public function quote($value)
    {
        return $this->DBInstance->quote($value);
    }
}