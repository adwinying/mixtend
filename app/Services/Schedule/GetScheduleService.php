<?php

namespace App\Services\Schedule;

use App\Exceptions\Mixtend\MixtendHttpException;
use App\Exceptions\Mixtend\MixtendScheduleException;
use App\Services\Schedule\Data\MeetingData;
use App\Services\Schedule\Data\ScheduleData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * スケジュールを API から取得し、日本時間に変換して日付ごとにまとめる。
 * レスポンスはキャッシュせず、呼び出しのたびに取得する。
 *
 * データについて以下を前提とする。前提が崩れたら表示ロジックの変更が必要。
 * - ミーティングは常に勤務時間内に収まる
 * - 勤務時間はミーティングと同じタイムゾーン（Asia/Tokyo）
 * - 重なりのレイアウトには未対応。重なるデータが現れたら warning を記録する
 */
class GetScheduleService
{
    private const string TOKYO_TIMEZONE = 'Asia/Tokyo';

    public function __construct(private ScheduleApiClient $client) {}

    /**
     * @throws MixtendHttpException Mixtend との通信の失敗
     * @throws MixtendScheduleException レスポンスが想定外の形式
     */
    public function run(): ScheduleData
    {
        $schedule = $this->validate($this->client->fetch());

        $meetingsByDate = [];
        foreach ($schedule['meetings'] as $date => $meetings) {
            foreach ($meetings as $meeting) {
                $start = $this->toTokyo($date, $meeting['start'], $meeting['timezone']);
                $end = $this->toTokyo($date, $meeting['end'], $meeting['timezone']);
                $meetingsByDate[$start->format('Y-m-d')][] = new MeetingData($meeting['summary'], $start->format('H:i'), $end->format('H:i'));
            }
        }
        ksort($meetingsByDate);
        $meetingsByDate = array_map($this->sortByStart(...), $meetingsByDate);
        array_walk($meetingsByDate, $this->warnOverlaps(...));

        return new ScheduleData(
            $schedule['working_hours']['start'],
            $schedule['working_hours']['end'],
            $meetingsByDate,
        );
    }

    private function toTokyo(string $date, string $time, string $timezone): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} {$time}", $timezone)->setTimezone(self::TOKYO_TIMEZONE);
    }

    /**
     * @param  list<MeetingData>  $meetings
     * @return list<MeetingData>
     */
    private function sortByStart(array $meetings): array
    {
        usort($meetings, fn (MeetingData $a, MeetingData $b) => $a->start <=> $b->start);

        return $meetings;
    }

    /**
     * 終了と開始が同時刻の連続するミーティングは重なりとみなさない。
     * 隣り合わないミーティングとの重なりも検出するため、それまでで最も遅く終わるミーティングと比べる。
     *
     * @param  list<MeetingData>  $meetings  開始時刻順
     */
    private function warnOverlaps(array $meetings, string $date): void
    {
        $latestEnding = null;
        foreach ($meetings as $meeting) {
            if ($latestEnding !== null && $meeting->start < $latestEnding->end) {
                Log::warning('同じ日のミーティングが重なっています', [
                    'date' => $date,
                    'previous' => (array) $latestEnding,
                    'next' => (array) $meeting,
                ]);
            }
            if ($latestEnding === null || $meeting->end > $latestEnding->end) {
                $latestEnding = $meeting;
            }
        }
    }

    /**
     * API のレスポンスは信頼境界なので、形式を検証してから使う。
     *
     * @param  array<mixed>  $schedule
     * @return array{working_hours: array{start: string, end: string}, meetings: array<string, list<array{summary: string, start: string, end: string, timezone: string}>>}
     *
     * @throws MixtendScheduleException
     */
    private function validate(array $schedule): array
    {
        try {
            Validator::make($schedule, [
                'working_hours' => ['required', 'array'],
                'working_hours.start' => ['required', 'date_format:H:i'],
                'working_hours.end' => ['required', 'date_format:H:i'],
                'meetings' => ['present', new MeetingsByDateRule],
                'meetings.*.*.summary' => ['required', 'string'],
                'meetings.*.*.start' => ['required', 'date_format:H:i'],
                'meetings.*.*.end' => ['required', 'date_format:H:i'],
                'meetings.*.*.timezone' => ['required', 'timezone:all'],
            ])->validate();
        } catch (ValidationException $exception) {
            throw new MixtendScheduleException('スケジュール API のレスポンスが想定外の形式です', ['errors' => $exception->errors()], $exception);
        }

        /** @var array{working_hours: array{start: string, end: string}, meetings: array<string, list<array{summary: string, start: string, end: string, timezone: string}>>} */
        return $schedule;
    }
}
