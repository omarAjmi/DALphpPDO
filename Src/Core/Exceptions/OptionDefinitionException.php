<?php

namespace App\Src\Core\Exceptions;

/**
 * Thrown when two lazy options have a cyclic dependency.
 *
 */
class OptionDefinitionException extends \LogicException implements ExceptionInterface
{
}
