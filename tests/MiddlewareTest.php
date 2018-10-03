<?php

namespace Popeye\Tests;

use PHPUnit\Framework\TestCase;
use Popeye\Middleware;

/**
 * Class MiddlewareTest
 * @package Popeye\Tests
 * @author Jarryd Tilbrook <jrad.tilbrook@gmail.com>
 */
class MiddlewareTest extends TestCase
{
    /**
     * @var \Popeye\Middleware
     */
    private $middleware;


    public function setUp()
    {
        $this->middleware = new Middleware;
    }


    /**
     * Ensure we can add a handler and can chain methods.
     */
    public function testAddHandler()
    {
        $r = $this->middleware->add(function () {
            // no-op
        });

        $this->assertSame($r, $this->middleware);
    }


    /**
     * Ensure `$next` will call the next handler.
     */
    public function testNextHandler()
    {
        $this->middleware
            ->add(function ($next) {
                $next();
            })
            ->add(function ($next) {
                $this->assertTrue(true);
            });

        $this->middleware->resolve();
    }


    /**
     * Ensure `$next` will call the next handler.
     */
    public function testNextHandlerInvoke()
    {
        $this->middleware
            ->add(function ($next) {
                $next();
            })
            ->add(function ($next) {
                $this->assertTrue(true);
            });

        call_user_func($this->middleware);
    }


    /**
     * Ensure that the last handler can call the last `$next` wrapper with no issue.
     */
    public function testFinalNextHandler()
    {
        $this->middleware
            ->add(function ($next) {
                $next();
            })
            ->add(function ($next) {
                $next();
                $this->assertTrue(true);
            });

        $this->middleware->resolve();
    }


    /**
     * Ensure a NoMiddlewareException is thrown if there are no registered handlers.
     *
     * @expectedException \Popeye\Exception\NoMiddlewareException
     * @expectedExceptionMessage Cannot call an empty middleware stack
     */
    public function testResolveWithNoHandlersThrowsException()
    {
        $this->middleware->resolve();
    }

    /**
     * Ensure any PHP7 Error thrown by a handler is caught and a HandlerException is thrown.
     *
     * @expectedException \Popeye\Exception\HandlerException
     * @expectedExceptionMessage Handler threw an exception
     */
    public function testMiddlewareCatchesHandlerError()
    {
        $this->middleware->add(function () {
            callUndefinedFunction();
        });

        $this->middleware->resolve();
    }

    /**
     * Ensure any exception thrown by a handler is caught and a HandlerException is thrown.
     *
     * @expectedException \Popeye\Exception\HandlerException
     * @expectedExceptionMessage Handler threw an exception
     */
    public function testMiddlewareCatchesHandlerException()
    {
        $this->middleware->add(function () {
            throw new \Exception('Test exception');
        });

        $this->middleware->resolve();
    }

    /**
     * Ensure that the middleware will return the value returned from a single handler.
     */
    public function testMiddlewareReturnsValue()
    {
        $this->middleware->add(function () {
            return 42;
        });

        $value = $this->middleware->resolve();

        $this->assertEquals(42, $value);
    }

    /**
     * Ensure that the middleware will return the value from the handler stack.
     */
    public function testMiddlewareReturnsValueFromTopHandler()
    {
        $this->middleware->add(function ($next) {
            return $next();
        })->add(function () {
            return 24;
        });

        $value = $this->middleware->resolve();

        $this->assertEquals(24, $value);
    }

    /**
     * Ensure that an exception is thrown trying to add to a running stack.
     *
     * @expectedException \Popeye\Exception\LockedStackException
     * @expectedExceptionMessage Cannot modify locked middleware
     */
    public function testAddingToRunningMiddlewareThrowsException()
    {
        $this->middleware->add(function () {
            $this->middleware->add(function () {
            });
            return 24;
        });

        $this->middleware->resolve();
    }

    /**
     * Ensure that the handlers are called in the order they are added.
     */
    public function testCorrectOrdering()
    {
        $a = 0;

        $this->middleware
            ->add(function ($next) use (&$a) {
                $a++;
                $b = $next();
                $this->assertEquals(2, $b);
                return $b;
            })
            ->add(function () use (&$a) {
                $a++;
                return $a;
            });

        $c = $this->middleware->resolve();

        $this->assertEquals(2, $c);
    }
}
