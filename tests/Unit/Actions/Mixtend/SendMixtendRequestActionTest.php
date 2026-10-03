<?php

use App\Actions\Mixtend\SendMixtendRequestAction;
use App\Enums\MixtendRoute;
use App\Exceptions\MixtendHttpException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

pest()->group('actions');

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.mixtend.base_url' => 'https://mixtend.test/api/']);
});

test('ベース URL とパスを結合し、User-Agent を付けて送信して JSON オブジェクトを返す', function () {
    Http::fake(['https://mixtend.test/api/schedule.json?week=1' => Http::response(['ok' => true])]);

    $body = app(SendMixtendRequestAction::class)->run(MixtendRoute::Schedule, ['week' => 1]);

    expect($body)->toBe(['ok' => true]);
    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->url() === 'https://mixtend.test/api/schedule.json?week=1'
        && $request->hasHeader('User-Agent', 'Mixtend Coding Test'));
});

test('非2xx では完全な URL とステータスを持つ MixtendHttpException を投げる', function () {
    Http::fake(['https://mixtend.test/api/schedule.json' => Http::response('Not Found', 404)]);

    expect(fn () => app(SendMixtendRequestAction::class)->run(MixtendRoute::Schedule))
        ->toThrow(fn (MixtendHttpException $e) => expect($e->context())->toBe([
            'url' => 'https://mixtend.test/api/schedule.json',
            'status' => 404,
        ]));
});

test('接続失敗でも、例外の context の url は解決後の完全な URL になる', function () {
    Http::fake(['https://mixtend.test/api/schedule.json?week=1' => Http::failedConnection()]);

    expect(fn () => app(SendMixtendRequestAction::class)->run(MixtendRoute::Schedule, ['week' => 1]))
        ->toThrow(fn (MixtendHttpException $e) => expect($e->context())->toBe([
            'url' => 'https://mixtend.test/api/schedule.json?week=1',
        ]));
});

test('JSON のルートが配列なら MixtendHttpException を投げる', function (string $body) {
    Http::fake(['https://mixtend.test/api/schedule.json' => Http::response($body)]);

    expect(fn () => app(SendMixtendRequestAction::class)->run(MixtendRoute::Schedule))
        ->toThrow(MixtendHttpException::class);
})->with(['空の配列' => '[]', '要素のある配列' => '[1]']);

test('空の JSON オブジェクトは空の配列として返す', function () {
    Http::fake(['https://mixtend.test/api/schedule.json' => Http::response('{}')]);

    expect(app(SendMixtendRequestAction::class)->run(MixtendRoute::Schedule))->toBe([]);
});
