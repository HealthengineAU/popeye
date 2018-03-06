<?php

namespace Popeye;

use Exception;
use Popeye\Exception\HandlerException;
use Popeye\Exception\NoMiddlewareException;
use SplQueue;
use Throwable;

/**
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
     * Create a new Middleware stack.
     */
    public function __construct()
    {
        $this->queue = new SplQueue;
    }


    /**
     * Call the stack passing in the supplied arguments.
     *
     * @param mixed $args Variable argument list to pass through the handlers.
     *
     * @return static
     *
     * @throws \Popeye\Exception\NoMiddlewareException If the queue is empty, ie. trying to resolve without adding
     * handlers.
     */
    public function resolve(...$args)
    {
        // update the queue pointer to the head of the queue
        $this->queue->rewind();

        // retrieve the head of the queue
        $top = $this->getNextHandler();

        try {
            // start calling the handlers passing in the arguments
            return $top(...$args);
        } catch (Exception $e) {
            throw new HandlerException('Handler threw an exception', null, $e);
        } catch (Throwable $t) {
            throw new HandlerException('Handler threw an error', null, $t);
        }

        return $this;
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
    private function getNextHandler()
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
     */
    public function add(callable $handler)
    {
        $this->queue->enqueue(function (...$args) use ($handler) {
            $nextWrapper = function () use ($args) {
                $next = $this->getNextHandler();
                return $next(...$args);
            };

            $args[] = $nextWrapper;

            return call_user_func($handler, ...$args);
        });

        return $this;
    }
}
