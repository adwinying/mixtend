<?php

namespace App\Http\Responses;

use Spatie\LaravelData\Data;

class ScheduleIndexResponseMeeting extends Data
{
    /**
     * @param  string  $start  H:i
     * @param  string  $end  H:i
     */
    public function __construct(
        public string $summary,
        public string $start,
        public string $end,
    ) {}
}
