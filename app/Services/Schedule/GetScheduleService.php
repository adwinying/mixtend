<?php

namespace App\Services\Schedule;

use App\Services\Schedule\Data\MeetingData;
use App\Services\Schedule\Data\ScheduleData;

/**
 * スケジュールを API から取得し、日付ごとにまとめる。
 * レスポンスはキャッシュせず、呼び出しのたびに取得する。
 */
class GetScheduleService
{
    public function __construct(private ScheduleApiClient $client) {}

    public function run(): ScheduleData
    {
        /** @var array{working_hours: array{start: string, end: string}, meetings: array<string, list<array{summary: string, start: string, end: string}>>} $schedule */
        $schedule = $this->client->fetch();

        $meetingsByDate = array_map(
            fn (array $meetings) => array_map(
                fn (array $meeting) => new MeetingData($meeting['summary'], $meeting['start'], $meeting['end']),
                $meetings,
            ),
            $schedule['meetings'],
        );
        ksort($meetingsByDate);

        return new ScheduleData(
            $schedule['working_hours']['start'],
            $schedule['working_hours']['end'],
            $meetingsByDate,
        );
    }
}
