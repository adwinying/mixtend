<?php

namespace App\Services\Schedule;

use App\Exceptions\Schedule\ScheduleException;
use App\Services\Schedule\Data\MeetingData;
use App\Services\Schedule\Data\ScheduleData;
use Closure;
use DateTimeImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * スケジュールを API から取得し、日付ごとにまとめる。
 * レスポンスはキャッシュせず、呼び出しのたびに取得する。
 */
class GetScheduleService
{
    public function __construct(private ScheduleApiClient $client) {}

    /**
     * @throws ScheduleException API の障害、またはレスポンスが想定外の形式
     */
    public function run(): ScheduleData
    {
        $schedule = $this->validate($this->client->fetch());

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

    /**
     * API のレスポンスは信頼境界なので、形式を検証してから使う。
     *
     * @param  array<mixed>  $schedule
     * @return array{working_hours: array{start: string, end: string}, meetings: array<string, list<array{summary: string, start: string, end: string, timezone: string}>>}
     *
     * @throws ScheduleException
     */
    private function validate(array $schedule): array
    {
        try {
            Validator::make($schedule, [
                'working_hours' => ['required', 'array'],
                'working_hours.start' => ['required', 'date_format:H:i'],
                'working_hours.end' => ['required', 'date_format:H:i'],
                'meetings' => ['present', 'array', $this->dateKeysRule(...)],
                'meetings.*' => ['list'],
                'meetings.*.*.summary' => ['required', 'string'],
                'meetings.*.*.start' => ['required', 'date_format:H:i'],
                'meetings.*.*.end' => ['required', 'date_format:H:i'],
                'meetings.*.*.timezone' => ['required', 'timezone:all'],
            ])->validate();
        } catch (ValidationException $exception) {
            throw new ScheduleException('スケジュール API のレスポンスが想定外の形式です', ['errors' => $exception->errors()], $exception);
        }

        /** @var array{working_hours: array{start: string, end: string}, meetings: array<string, list<array{summary: string, start: string, end: string, timezone: string}>>} */
        return $schedule;
    }

    /**
     * meetings は Y-m-d の日付をキーとするマップ。キーは Validator のルールで検証できないため、クロージャで検証する。
     */
    private function dateKeysRule(string $attribute, mixed $meetings, Closure $fail): void
    {
        foreach (array_keys((array) $meetings) as $date) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $date);
            if ($parsed === false || $parsed->format('Y-m-d') !== (string) $date) {
                $fail("{$attribute} のキー {$date} が Y-m-d 形式の日付ではありません。");
            }
        }
    }
}
