<?php

namespace Popeye\Exception;

/**
 * Class NoMiddlewareException
 * @package Popeye\Exception
 * @author Jarryd Tilbrook <jrad.tilbrook@gmail.com>
 *
 * An exception of this type is thrown when trying to resolve a middleware stack that has no registered handlers.
 */
class NoMiddlewareException extends Exception
{
}
