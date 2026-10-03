<?php

namespace App\Actions\Mixtend;

use App\Enums\MixtendRoute;
use App\Exceptions\MixtendHttpException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Log;
use JsonException;
use stdClass;

/**
 * Mixtend にリクエストを送信し、レスポンスを mixtend チャネルに記録する。
 */
class SendMixtendRequestAction
{
    public function __construct(private GetMixtendHttpClientAction $getHttpClient) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     *
     * @throws MixtendHttpException 接続失敗・タイムアウト・非2xx・不正な JSON・JSON オブジェクト以外のレスポンス
     */
    public function run(MixtendRoute $route, array $query = []): array
    {
        // 接続失敗ではレスポンスが無いため、ベース URL とクエリを解決した後の URL を送信直前に控える
        $url = $route->value;
        $client = $this->getHttpClient->run()->beforeSending(function (Request $request) use (&$url) {
            $url = $request->url();
        });

        try {
            $response = $client->send($route->method(), $route->value, ['query' => $query]);
        } catch (ConnectionException $exception) {
            throw new MixtendHttpException('Mixtend に接続できません', ['url' => $url], $exception);
        }

        Log::channel('mixtend')->info('Mixtend のレスポンス', [
            'url' => $url,
            'status' => $response->status(),
            // JSON ならログの JSON に入れ子で記録し、それ以外（HTML のエラー応答など）は文字列のまま記録する
            'body' => $response->json() ?? $response->body(),
        ]);

        $context = ['url' => $url, 'status' => $response->status()];

        // failed() は 4xx / 5xx のみを対象とし、リダイレクト先のない 3xx を通してしまう
        if (! $response->successful()) {
            throw new MixtendHttpException('Mixtend がエラーを返しました', $context, $response->toException());
        }

        try {
            $body = $response->json(flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MixtendHttpException('Mixtend のレスポンスが不正な JSON です', $context, $exception);
        }

        // json() はオブジェクトも配列も PHP の配列にするため、ルートがオブジェクトかは object() で確かめる
        if (! is_array($body) || ! $response->object() instanceof stdClass) {
            throw new MixtendHttpException('Mixtend のレスポンスが JSON オブジェクトではありません', $context);
        }

        return $body;
    }
}
