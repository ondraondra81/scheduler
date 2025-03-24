<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz;

use Crunz\Schedule;

class ScheduleCollection
{
    /**
     * @var Schedule[]
     */
    private array $schedules = [];

    public function add(Schedule $schedule): void
    {
        $this->schedules[] = $schedule;
    }


    /**
     * @return Schedule[]
     */
    public function all(): array
    {
        return $this->schedules;
    }
}
