<?php

namespace Popeye;

use Exception;
use Popeye\Exception\Exception as PopeyeException;
use Popeye\Exception\HandlerException;
use Popeye\Exception\LockedStackException;
use Popeye\Exception\NoMiddlewareException;
use SplQueue;
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
    /**
     * Handler callable queue.
     *
     * @var \SplQueue
     * @link http://php.net/manual/class.splstack.php
     */
    private $queue;

    /**
     * Whether more middleware can be added. This is set to true when the resolve method starts to ensure
     * @var bool
     */
    private $lock;


    /**
     * Create a new Middleware stack.
     */
    public function __construct()
    {
        $this->queue = new SplQueue;
        $this->unlock();
    }


    /**
     * Lock the stack so additional handlers cannot be added.
     * @return static
     */
    protected function lock()
    {
        $this->lock = true;
        return $this;
    }


    /**
     * Unlock the stack to allow handlers to be added.
     * @return static
     */
    protected function unlock()
    {
        $this->lock = false;
        return $this;
    }


    /**
     * Whether the stack is locked and no handlers may be added.
     * @return bool
     */
    public function isLocked()
    {
        return $this->lock;
    }


    /**
     * Call the stack passing in the supplied arguments.
     *
     * @param mixed $args Variable argument list to pass through the handlers.
     *
     * @return mixed The value returned from the middleware stack.
     *
     * @throws \Popeye\Exception\NoMiddlewareException If the queue is empty, ie. trying to resolve without adding
     * handlers.
     */
    public function resolve(...$args)
    {
        // lock the stack so handlers cannot make runtime modifications
        $this->lock();
        // update the queue pointer to the head of the queue
        $this->queue->rewind();

        // retrieve the head of the queue
        $top = $this->getNextHandler();

        try {
            // start calling the handlers passing in the arguments
            $resolved = $top(...$args);
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
     * Retrieves the next closure in the queue and updates the queue pointer.
     *
     * At the end of the queue it returns an empty closure so the last handler can call a valid `$next` function.
     *
     * @return callable The next wrapper function on the queue.
     *
     * @throws \Popeye\Exception\NoMiddlewareException If the queue is empty, ie. trying to resolve without adding
     * handlers.
     */
    protected function getNextHandler()
    {
        if ($this->queue->isEmpty()) {
            throw new NoMiddlewareException('Cannot call an empty middleware stack');
        } elseif (!$this->queue->valid()) {
            // this occurs as the end of the queue, so return an empty wrapper function
            $next = function () {
                // no-op
            };
        } else {
            $next = $this->queue->current();
            $this->queue->next();
        }

        return $next;
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

        $this->queue->enqueue(function (...$args) use ($handler) {
            $nextWrapper = function () use ($args) {
                $next = $this->getNextHandler();
                return $next(...$args);
            };

            $args[] = $nextWrapper;

            // proxy actually calling the handler to allow overriding in specifics of calling
            return $this->callHandler($handler, ...$args);
        });

        return $this;
    }


    /**
     * Allow for extensibility in handler types. This method may be overridden to call the handler in a more specific
     * way - useful if the callable is actually a class that needs instantiation and dependency injection.
     *
     * @param callable $handler Any callable to call.
     * @param mixed $args Variable argument list to pass to the handler.
     *
     * @return mixed Whatever is returned by calling the handler.
     */
    protected function callHandler(callable $handler, ...$args)
    {
        return call_user_func($handler, ...$args);
    }
}
