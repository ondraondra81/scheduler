<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Scheduler\Contract\Event as EventInterface;
use App\Scheduler\Exception\InvalidArgumentException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

use function array_intersect_key;
use function count;
use function date;
use function date_parse;
use function explode;
use function implode;

class Event implements EventInterface
{
    /**
     * @var array<string, int>
     */
    protected array $fieldsPosition = [
        'minute' => 1,
        'hour' => 2,
        'day' => 3,
        'month' => 4,
        'week' => 5,
    ];
    /**
     * Datetime or time since the task is evaluated and possibly executed only for display purposes.
     */
    protected DateTime|string|null $from = null;
    /**
     * Datetime or time until the task is evaluated and possibly executed only for display purposes.
     */
    protected DateTime|string|null $to = null;
    private string $expression = '* * * * *';
    /**
     * @var array<int, Closure(): bool>
     */
    private array $runCondition = [];
    /**
     * @var array<int, Closure(): bool>
     */
    private array $skipCondition = [];

    public function __construct()
    {
    }

    public function cron(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    public function everyMinutes(int $minutes): self
    {
        if ($minutes <= 1 || $minutes >= 59) {
            throw new InvalidArgumentException('Minutes must be between 1 and 59');
        }

        return $this->cron('*/' . $minutes . ' * * * *');
    }

    public function everyHour(): self
    {
        return $this->cron('0 */1 * * *');
    }

    public function everyHours(int $hours): self
    {
        if ($hours <= 1 || $hours >= 23) {
            throw new InvalidArgumentException('Hours must be between 1 and 23');
        }

        return $this->cron('0 */' . $hours . ' * * *');
    }

    public function on(string $date): self
    {
        $parsedDate = date_parse($date);
        if ($parsedDate['error_count'] > 0) {
            throw new InvalidArgumentException("Invalid date string: $date");
        }

        $segments = array_intersect_key($parsedDate, $this->fieldsPosition);

        if ($parsedDate['year']) {
            $this->skip(static fn() => (int)date('Y') !== $parsedDate['year']);
        }

        foreach ($segments as $key => $value) {
            if (false !== $value) {
                //@phpstan-ignore-next-line
                $this->spliceIntoPosition($this->fieldsPosition[$key], (string)$value);
            }
        }

        return $this;
    }

    /**
     * Schedule the command at a given time.
     */
    public function at(string $time): self
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     */
    public function dailyAt(string $time): self
    {
        $segments = explode(':', $time);
        $firstSegment = (int)$segments[0];
        $secondSegment = (int)($segments[1] ?? 0);

        return $this
            ->spliceIntoPosition(2, (string)$firstSegment)
            ->spliceIntoPosition(1, (string)$secondSegment);
    }

    /**
     * Set Working period.
     */
    public function between(string $from, string $to): self
    {
        return $this->from($from)
            ->to($to);
    }

    /**
     * Check if event should be on.
     */
    public function from(string $datetime): self
    {
        $this->from = $datetime;

        return $this->skip(
            fn(DateTimeZone $timeZone) => $this->notYet($datetime, $timeZone)
        );
    }

    /**
     * Check if event should be off.
     */
    public function to(string $datetime): self
    {
        $this->to = $datetime;

        return $this->skip(
            fn(DateTimeZone $timeZone) => $this->past($datetime, $timeZone),
        );
    }

    // další zkratky ...

    public function addRunCondition(Closure $condition): self
    {
        $this->runCondition[] = $condition;

        return $this;
    }


    public function skip(Closure $condition): self
    {
        $this->skipCondition[] = $condition;

        return $this;
    }

    /**
     * @return array<int, Closure>
     */
    public function runConditions(): array
    {
        return $this->runCondition;
    }

    /**
     * @return array<int, Closure>
     */
    public function skipConditions(): array
    {
        return $this->skipCondition;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    protected function spliceIntoPosition(int $position, string $value): self
    {
        $segments = explode(' ', $this->expression);

        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }

    protected function notYet(string $datetime, DateTimeZone $timeZone): bool
    {
        $timeZonedNow = $this->timeZonedNow($timeZone);
        $testedDateTime = new DateTimeImmutable($datetime, $timeZone);

        return $timeZonedNow < $testedDateTime;
    }

    protected function past(string $datetime, DateTimeZone $timeZone): bool
    {
        $timeZonedNow = $this->timeZonedNow($timeZone);
        $testedDateTime = new DateTimeImmutable($datetime, $timeZone);

        return $timeZonedNow > $testedDateTime;
    }

    private function timeZonedNow(DateTimeZone $timeZone): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $timeZone);
    }
}
