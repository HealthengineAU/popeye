<?php

namespace Popeye;

use Popeye\Exception\Exception as PopeyeException;
use Popeye\Exception\HandlerException;
use Popeye\Exception\LockedStackException;
use Throwable;

/**
 * Class Middleware
 * @package Popeye
 * @author Jarryd Tilbrook <jrad.tilbrook@gmail.com>
 *
 * This is a generic middleware stack. It allows for a functional approach to handling data without imposing
 * restrictions on the data. Type safety/checking is left up to the user to implement.
 */
class Middleware
{
    use HasMiddleware, Lockable;


    /**
     * Initialise the queue and set it unlocked.
     */
    public function __construct()
    {
        $this->initialiseQueue();
        $this->unlock();
    }


    /**
     * Call the stack passing in the supplied arguments.
     *
     * @param mixed $args Variable argument list to pass through the handlers.
     *
     * @return mixed The value returned from the middleware stack.
     *
     * @throws \Popeye\Exception\HandlerException If an invoked handler throws an exception.
     * @throws \Popeye\Exception\Exception
     */
    public function resolve(...$args)
    {
        // lock the stack so handlers cannot make runtime modifications
        $this->lock();

        try {
            // start calling the handlers passing in the arguments
            $resolved = $this->runStack(...$args);
        } catch (PopeyeException $e) {
            // catch any package specific exceptions and rethrow them
            throw $e;
        } catch (Throwable $t) {
            // unlock the stack now that we have finished
            $this->unlock();
            throw new HandlerException('Handler threw an exception', null, $t);
        }

        // unlock the stack now that we have finished
        $this->unlock();

        return $resolved;
    }


    /**
     * Add a new handler to the back of the queue.
     *
     * @param callable $handler Any callable
     *
     * @return static
     *
     * @throws \Popeye\Exception\LockedStackException If the stack is locked and cannot be modified.
     */
    public function add(callable $handler)
    {
        // abort early if the stack is locked
        if ($this->isLocked()) {
            throw new LockedStackException('Cannot modify locked middleware');
        }

        $this->addHandler($handler);

        return $this;
    }
}
