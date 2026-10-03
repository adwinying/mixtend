<?php

namespace App\Services\Schedule\Data;

final readonly class ScheduleData
{
    /**
     * @param  string  $workingHoursStart  H:i
     * @param  string  $workingHoursEnd  H:i
     * @param  array<string, list<MeetingData>>  $meetingsByDate  Y-m-d をキーとし、日付順に並ぶ
     */
    public function __construct(
        public string $workingHoursStart,
        public string $workingHoursEnd,
        public array $meetingsByDate,
    ) {}
}
