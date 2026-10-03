<?php

namespace App\Http\Controllers;

use App\Actions\Schedule\GetScheduleAction;
use App\Exceptions\Mixtend\MixtendException;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\ScheduleIndexResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ScheduleIndexController extends Controller
{
    /**
     * API の障害は環境を問わず 502 のエラーページにする。同じ URL のままなので、再読み込みで再試行できる。
     */
    public function __invoke(Request $request, GetScheduleAction $getSchedule): Response|SymfonyResponse
    {
        try {
            $schedule = $getSchedule->run();
        } catch (MixtendException $exception) {
            report($exception);

            return Inertia::render('error', ErrorResponse::fromException(502, $exception))
                ->toResponse($request)
                ->setStatusCode(502);
        }

        return Inertia::render('schedule-index', ScheduleIndexResponse::fromSchedule($schedule));
    }
}
