<?php

namespace App\Http\Controllers;

use App\Http\Responses\ScheduleIndexResponse;
use App\Services\Schedule\GetScheduleService;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleIndexController extends Controller
{
    public function __invoke(GetScheduleService $getSchedule): Response
    {
        return Inertia::render('schedule-index', ScheduleIndexResponse::fromSchedule($getSchedule->run()));
    }
}
