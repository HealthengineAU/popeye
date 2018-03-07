<?php

namespace Popeye;

use Popeye\Exception\NoMiddlewareException;
use SplQueue;

/**
 * Trait HasMiddleware
 * @package Popeye
 * @author Jarryd Tilbrook <jrad.tilbrook@gmail.com>
 *
 * This is a generic middleware trait that provides methods for interacting with the queue and running through it.
 */
trait HasMiddleware
{
    /**
     * Handler callable queue.
     *
     * @var \SplQueue
     * @link http://php.net/manual/class.splstack.php
     */
    private $queue;


    /**
     * Initialise the middleware queue.
     * @return static
     */
    private function initialiseQueue()
    {
        $this->queue = new SplQueue;
        return $this;
    }


    /**
     * Add a handler to the stack.
     *
     * @param callable $handler
     * @return static
     */
    protected function addHandler(callable $handler)
    {
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
     * Run through the stack passing the provided arguments to each handler in-turn.
     *
     * @param array $args
     *
     * @return mixed Whatever is returned from the handler functions.
     *
     * @throws \Popeye\Exception\NoMiddlewareException
     * @throws \Throwable
     */
    protected function runStack(...$args)
    {
        // update the queue pointer to the head of the queue
        $this->queue->rewind();

        // retrieve the head of the queue
        $top = $this->getNextHandler();

        // start calling the handlers passing in the arguments
        $resolved = $top(...$args);

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
