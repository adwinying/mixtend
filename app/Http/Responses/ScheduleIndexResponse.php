<?php

namespace App\Http\Responses;

use App\Services\Schedule\Data\ScheduleData;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ScheduleIndexResponse extends Data
{
    /**
     * @param  list<ScheduleIndexResponseDay>  $days  データに存在する日付だけを日付順に並べる
     * @param  list<string>  $hours  勤務時間の開始〜終了の各正時（H:i、終端を含む）
     */
    public function __construct(
        public array $days,
        public array $hours,
    ) {}

    public static function fromSchedule(ScheduleData $schedule): self
    {
        $days = [];
        foreach ($schedule->meetingsByDate as $date => $meetings) {
            $days[] = ScheduleIndexResponseDay::fromMeetings($date, $meetings);
        }

        $hours = array_map(
            fn (int $hour) => sprintf('%02d:00', $hour),
            range(
                CarbonImmutable::createFromFormat('H:i', $schedule->workingHoursStart)->hour,
                CarbonImmutable::createFromFormat('H:i', $schedule->workingHoursEnd)->hour,
            ),
        );

        return new self($days, $hours);
    }
}
