<?php

namespace App\Actions\Mixtend;

use App\Enums\MixtendRoute;
use App\Exceptions\Mixtend\MixtendHttpException;
use App\Exceptions\Mixtend\MixtendScheduleException;
use App\Rules\MixtendMeetingsByDateRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Mixtend からスケジュールを取得する。レスポンスは信頼境界なので、形式を検証してから返す。
 */
class GetMixtendScheduleAction
{
    public function __construct(private SendMixtendRequestAction $sendMixtendRequest) {}

    /**
     * @return array{working_hours: array{start: string, end: string}, meetings: array<string, list<array{summary: string, start: string, end: string, timezone: string}>>}
     *
     * @throws MixtendHttpException Mixtend との通信の失敗
     * @throws MixtendScheduleException レスポンスが想定外の形式
     */
    public function run(): array
    {
        $schedule = $this->sendMixtendRequest->run(MixtendRoute::Schedule);

        try {
            Validator::make($schedule, [
                'working_hours' => ['required', 'array'],
                'working_hours.start' => ['required', 'date_format:H:i'],
                'working_hours.end' => ['required', 'date_format:H:i'],
                'meetings' => ['present', new MixtendMeetingsByDateRule],
                'meetings.*.*.summary' => ['required', 'string'],
                'meetings.*.*.start' => ['required', 'date_format:H:i'],
                'meetings.*.*.end' => ['required', 'date_format:H:i'],
                'meetings.*.*.timezone' => ['required', 'timezone:all'],
            ])->validate();
        } catch (ValidationException $exception) {
            throw new MixtendScheduleException('スケジュールのレスポンスが想定外の形式です', ['errors' => $exception->errors()], $exception);
        }

        /** @var array{working_hours: array{start: string, end: string}, meetings: array<string, list<array{summary: string, start: string, end: string, timezone: string}>>} */
        return $schedule;
    }
}
