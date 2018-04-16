<?php

namespace App\Src\Core\Exceptions;
/**
 * Exception thrown when an undefined option is passed.
 *
 * You should remove the options in question from your code or define them
 * beforehand.
 *
 */
class UndefinedOptionsException extends InvalidArgumentException
{
}
