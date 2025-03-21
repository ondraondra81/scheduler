<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Scheduler\Contract\Mutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\FlockStore;

class FileMutex implements Mutex, EventMutex
{
    private LockFactory $lockFactory;
    /**
     * @var array<string, SharedLockInterface>
     */
    private array $locks = [];

    public function __construct(string $path)
    {
        $store = new FlockStore($path);
        $this->lockFactory = new LockFactory($store);
    }

    public function acquire(string $key, int $ttl): bool
    {
        if (isset($this->locks[$key])) {
            return false;
        }

        $lock = $this->lockFactory->createLock($key, $ttl);
        if (!$lock->acquire()) {
            return false;
        }

        $this->locks[$key] = $lock;
        return true;
    }

    public function release(string $key): void
    {
        if (isset($this->locks[$key])) {
            $this->locks[$key]->release();
            unset($this->locks[$key]);
        }
    }

    public function isLocked(string $key): bool
    {
        if (!isset($this->locks[$key])) {
            return false;
        }

        return $this->locks[$key]->isAcquired();
    }

    public function create(Event $event): bool
    {
        return $this->acquire($event->mutexName(), $event->expiresAt * 60);
    }

    public function exists(Event $event): bool
    {
        return $this->isLocked($event->mutexName());
    }

    public function forget(Event $event): void
    {
        $this->release($event->mutexName());
    }
}
