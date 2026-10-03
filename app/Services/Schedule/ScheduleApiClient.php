<?php

namespace App\Services\Schedule;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     */
    public function fetch(): array
    {
        $url = config()->string('services.schedule.url');

        $response = Http::withUserAgent(self::USER_AGENT)
            ->timeout(self::TIMEOUT_SECONDS)
            ->get($url);

        Log::channel('schedule')->info('スケジュール API のレスポンス', [
            'url' => $url,
            'status' => $response->status(),
            // JSON ならログの JSON に入れ子で記録し、それ以外（HTML のエラー応答など）は文字列のまま記録する
            'body' => $response->json() ?? $response->body(),
        ]);

        return (array) $response->json();
    }
}
