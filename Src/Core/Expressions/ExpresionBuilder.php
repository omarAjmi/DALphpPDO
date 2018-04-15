<?php
namespace Src\Core\Exceptions;

use Src\Libs\DatabaseConnection;


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
     * @var Src\Libs\DatabaseConnection
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

    public function equal(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, self::EQ, $param2);
    }

    public function notEqual(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, self::NEQ, $param2);
    }

    public function graterThen(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, self::GT, $param2);
    }

    public function graterThenOrEqual(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, self::GTE, $param2);
    }

    public function lessThen(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, self::LT, $param2);
    }

    public function lessThenOrEqual(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, self::LTE, $param2);
    }

    public function isNull(mixed $param)
    {
        return $param . ' IS NULL';
    }

    public function isNotNull(mixed $param)
    {
        return $param . ' IS NOT NULL';
    }

    public function like(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, ' LIKE ', $param2);
    }

    public function notLike(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, ' LIKE ', $param2);
    }

    public function in(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, ' IN ', implode(', ', (array)$param2));
    }

    public function notIn(mixed $param1, mixed $param2)
    {
        return $this->compare($param1, ' NOT IN ', implode(', ', (array)$param2));
    }

    public function literal(mixed $param)
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

    public function count(mixed $param)
    {
        is_array($param) ?
                        'COUNT (' . implode(',', $param) . ') ' :
                        'COUNT (' . $param . ') ';
    }
}
