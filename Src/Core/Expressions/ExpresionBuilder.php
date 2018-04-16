<?php
namespace App\Src\Core\Exceptions;

use App\Src\Libs\DatabaseConnection;


class ExpressionBuilder
{
    const EQ = '=';
    const NEQ = '<>';
    const LT = '<';
    const LTE = '<=';
    const GT = '>';
    const GTE = '>=';

    /**
     * connection instance
     *
     * @varApp\Src\Libs\DatabaseConnection
     */
    private $DBInstance;

    /**
     * Constructor
     *
     * @param DatabaseConnection $conn
     */
    public function __construct(DatabaseConnection $conn)
    {
        $this->DBInstance = $conn;
    }

    public function equal($param1, $param2)
    {
        return $this->compare($param1, self::EQ, $param2);
    }

    public function notEqual($param1, $param2)
    {
        return $this->compare($param1, self::NEQ, $param2);
    }

    public function graterThen($param1, $param2)
    {
        return $this->compare($param1, self::GT, $param2);
    }

    public function graterThenOrEqual($param1, $param2)
    {
        return $this->compare($param1, self::GTE, $param2);
    }

    public function lessThen($param1, $param2)
    {
        return $this->compare($param1, self::LT, $param2);
    }

    public function lessThenOrEqual($param1, $param2)
    {
        return $this->compare($param1, self::LTE, $param2);
    }

    public function isNull($param)
    {
        return $param . ' IS NULL';
    }

    public function isNotNull($param)
    {
        return $param . ' IS NOT NULL';
    }

    public function like($param1, $param2)
    {
        return $this->compare($param1, ' LIKE ', $param2);
    }

    public function notLike($param1, $param2)
    {
        return $this->compare($param1, ' LIKE ', $param2);
    }

    public function in($param1, $param2)
    {
        return $this->compare($param1, ' IN ', implode(', ', (array)$param2));
    }

    public function notIn($param1, $param2)
    {
        return $this->compare($param1, ' NOT IN ', implode(', ', (array)$param2));
    }

    public function literal($param)
    {
        return $this->DBInstance->quote($param);
    }

    public function between($input, $param1, $param2)
    {
        if (!is_numeric($param1) or !is_numeric($param2))
            return false;
        if ($param1 > $param2) {
            $tmp = $param1;
            $param1 = $param2;
            $param2 = $tmp;
        }
        return $this->compare($input, 'BETWEEN', $param1 . ' AND ' . $param2);
    }

    public function exists(string $subQuery)
    {
        return 'EXISTS (' . $subQuery . ') ';
    }

    public function count($param)
    {
        is_array($param) ?
                        'COUNT (' . implode(',', $param) . ') ' :
                        'COUNT (' . $param . ') ';
    }
}
