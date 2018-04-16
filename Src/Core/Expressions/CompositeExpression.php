<?php
/**
 * @author Omar A.Ajmi
 * @email devdevoops@gmail.com
 * @create date 2018-04-16 02:56:41
 * @modify date 2018-04-16 02:56:41
 * @desc [description]
*/

namespace App\Src\Core\Expressions;


class CompositeExpression
{
    /**
     * Constant that represents an AND composite expression.
     */
    const TYPE_AND = 'AND';

    /**
     * Constant that represents an OR composite expression.
     */
    const TYPE_OR = 'OR';

    /**
     * The instance type of composite expression.
     *
     * @var string
     */
    private $type;

    /**
     * Each expression part of the composite expression.
     *
     * @var array
     */
    private $parts = array();

    /**
     * Constructor.
     *
     * @param string $type  Instance type of composite expression.
     * @param array  $parts Composition of expressions to be joined on composite expression.
     */
    public function __construct($type, array $parts = array())
    {
        $this->type = $type;

        $this->addMultiple($parts);
    }

    /**
     * Adds multiple parts to composite expression.
     *
     * @param array $parts
     *
     * @return Src\Core\Expressions\CompositeExpression
     */
    public function addMultiple(array $parts = array())
    {
        foreach ((array)$parts as $part) {
            $this->add($part);
        }

        return $this;
    }

    /**
     * Adds an expression to composite expression.
     *
     * @param mixed $part
     *
     * @return Src\Core\Expressions\CompositeExpression
     */
    public function add($part)
    {
        if (!empty($part) || ($part instanceof self && $part->count() > 0)) {
            $this->parts[] = $part;
        }

        return $this;
    }

    /**
     * Retrieves the amount of expressions on composite expression.
     *
     * @return integer
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * Retrieves the string representation of this composite expression.
     *
     * @return string
     */
    public function __toString()
    {
        if (count($this->parts) === 1) {
            return (string)$this->parts[0];
        }

        return '(' . implode(') ' . $this->type . ' (', $this->parts) . ')';
    }

    /**
     * Returns the type of this composite expression (AND/OR).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}