<?php

namespace App\Actions\Mixtend;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Mixtend 専用の HTTP クライアントを生成する。全エンドポイント共通の設定はここに置く。
 */
class GetMixtendHttpClient
{
    private const string USER_AGENT = 'Mixtend Coding Test';

    private const int TIMEOUT_SECONDS = 5;

    public function run(): PendingRequest
    {
        return Http::baseUrl(config()->string('services.mixtend.base_url'))
            ->withUserAgent(self::USER_AGENT)
            ->timeout(self::TIMEOUT_SECONDS);
    }
}
