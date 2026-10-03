<?php

namespace App\Services\Schedule;

use App\Exceptions\Mixtend\MixtendHttpException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * スケジュール API との HTTP 通信だけを担当する。
 */
class ScheduleApiClient
{
    private const string USER_AGENT = 'Mixtend Coding Test';

    private const int TIMEOUT_SECONDS = 5;

    /**
     * スケジュールを取得し、レスポンスを schedule チャネルに記録する。
     *
     * @return array<mixed>
     *
     * @throws MixtendHttpException 接続失敗・タイムアウト・非2xx・不正な JSON・JSON オブジェクト以外のレスポンス
     */
    public function fetch(): array
    {
        $url = config()->string('services.schedule.url');

        try {
            $response = Http::withUserAgent(self::USER_AGENT)
                ->timeout(self::TIMEOUT_SECONDS)
                ->get($url);
        } catch (ConnectionException $exception) {
            throw new MixtendHttpException('スケジュール API に接続できません', ['url' => $url], $exception);
        }

        Log::channel('schedule')->info('スケジュール API のレスポンス', [
            'url' => $url,
            'status' => $response->status(),
            // JSON ならログの JSON に入れ子で記録し、それ以外（HTML のエラー応答など）は文字列のまま記録する
            'body' => $response->json() ?? $response->body(),
        ]);

        $context = ['url' => $url, 'status' => $response->status()];

        // failed() は 4xx / 5xx のみを対象とし、リダイレクト先のない 3xx を通してしまう
        if (! $response->successful()) {
            throw new MixtendHttpException('スケジュール API がエラーを返しました', $context, $response->toException());
        }

        try {
            $schedule = $response->json(flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MixtendHttpException('スケジュール API のレスポンスが不正な JSON です', $context, $exception);
        }

        if (! is_array($schedule)) {
            throw new MixtendHttpException('スケジュール API のレスポンスが JSON オブジェクトではありません', $context);
        }

        return $schedule;
    }
}
