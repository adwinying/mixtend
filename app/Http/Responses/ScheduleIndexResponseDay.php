<?php

namespace App\Http\Responses;

use App\Actions\Schedule\Data\MeetingData;
use Spatie\LaravelData\Data;

class ScheduleIndexResponseDay extends Data
{
    /**
     * @param  string  $date  Y-m-d
     * @param  list<ScheduleIndexResponseMeeting>  $meetings
     */
    public function __construct(
        public string $date,
        public array $meetings,
    ) {}

    /**
     * @param  list<MeetingData>  $meetings
     */
    public static function fromMeetings(string $date, array $meetings): self
    {
        return new self($date, array_map(
            fn (MeetingData $meeting) => new ScheduleIndexResponseMeeting($meeting->summary, $meeting->start, $meeting->end),
            $meetings,
        ));
    }
}
