<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use App\Scheduler\Event;
use App\Scheduler\Exception\InvalidArgumentException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use DateTimeZone;

class EventTest extends TestCase
{
    public function testCronExpressionCanBeSet(): void
    {
        $event = new Event();
        $event->cron('0 12 * * *');

        self::assertSame('0 12 * * *', $event->getExpression());
    }

    public function testEveryMinuteSetsCorrectExpression(): void
    {
        $event = new Event();
        $event->everyMinute();

        self::assertSame('* * * * *', $event->getExpression());
    }

    public function testEveryMinutesThrowsExceptionWhenOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minutes must be between 1 and 59');

        $event = new Event();
        $event->everyMinutes(0);
    }

    public function testEveryMinutesSetsCorrectExpression(): void
    {
        $event = new Event();
        $event->everyMinutes(15);

        self::assertSame('*/15 * * * *', $event->getExpression());
    }

    public function testEveryHourSetsCorrectExpression(): void
    {
        $event = new Event();
        $event->everyHour();

        self::assertSame('0 */1 * * *', $event->getExpression());
    }

    public function testEveryHoursThrowsExceptionWhenOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hours must be between 1 and 23');

        $event = new Event();
        $event->everyHours(24);
    }

    public function testEveryHoursSetsCorrectExpression(): void
    {
        $event = new Event();
        $event->everyHours(3);

        self::assertSame('0 */3 * * *', $event->getExpression());
    }

    public function testOnThrowsExceptionForInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date string: invalid-date');

        $event = new Event();
        $event->on('invalid-date');
    }

    public function testDailyAtSetsCorrectExpression(): void
    {
        $event = new Event();
        $event->dailyAt('15:30');

        self::assertSame('30 15 * * *', $event->getExpression());
    }

    public function testBetweenSetsFromAndToCorrectly(): void
    {
        $event = new Event();
        $event->between('2023-01-01 00:00', '2023-12-31 23:59');

        $skip = $event->skipConditions();
        self::assertCount(2, $skip);
    }

    public function testFromSetsSkipCondition(): void
    {
        $event = new Event();
        $datetime = new DateTimeImmutable('+1 day', new DateTimeZone('UTC'));

        $event->from($datetime->format('Y-m-d H:i:s'));

        $skipConditions = $event->skipConditions();
        $this->assertNotEmpty($skipConditions);

        $timeZone = new DateTimeZone('UTC');

        self::assertTrue($skipConditions[0]($timeZone));
    }
}
