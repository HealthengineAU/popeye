<?php

namespace Popeye\Exception;

/**
 * Class LockedStackException
 * @package Popeye\Exception
 * @author Jarryd Tilbrook <jrad.tilbrook@gmail.com>
 *
 * An exception of this type is thrown trying to modify a handler stack that has been locked.
 */
class LockedStackException extends Exception
{
}
