<?php

namespace Src\Core\Exceptions;
/**
 * Thrown when the value of an option does not match its validation rules.
 *
 * You should make sure a valid value is passed to the option.
 *
 */
class InvalidOptionsException extends \Exception
{
}
