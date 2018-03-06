<?php

namespace Popeye;

/**
 * Trait Lockable
 * @package Popeye
 * @author Jarryd Tilbrook <jrad.tilbrook@gmail.com>
 *
 * This is a generic locking trait that can be used to control access to something.
 * Similar to a mutex or semaphore.
 */
trait Lockable
{
    /**
     * @var bool
     */
    private $lock;

    /**
     * Enable the lock.
     * @return static
     */
    protected function lock()
    {
        $this->lock = true;
        return $this;
    }

    /**
     * Disable the lock.
     * @return static
     */
    protected function unlock()
    {
        $this->lock = false;
        return $this;
    }

    /**
     * Check whether the lock is currently enable or not.
     * @return bool
     */
    public function isLocked()
    {
        return $this->lock;
    }
}
