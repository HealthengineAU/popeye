<?php

namespace Popeye\Exception;

/**
 * An exception of this type is thrown when trying to resolve a middleware stack that has no registered handlers.
 */
class NoMiddlewareException extends Exception
{
}
