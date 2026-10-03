<?php

namespace App\Actions\Schedule\Data;

final readonly class MeetingData
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
