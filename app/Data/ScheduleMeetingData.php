<?php

namespace App\Data;

final readonly class ScheduleMeetingData
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
