<?php

use App\Actions\Mixtend\SendMixtendRequest;
use App\Enums\MixtendRoute;
use App\Exceptions\Mixtend\MixtendHttpException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.mixtend.base_url' => 'https://mixtend.test/api/']);
});

test('ベース URL とパスを結合し、User-Agent を付けて送信して JSON オブジェクトを返す', function () {
    Http::fake(['https://mixtend.test/api/schedule.json?week=1' => Http::response(['ok' => true])]);

    $body = app(SendMixtendRequest::class)->run(MixtendRoute::Schedule, ['week' => 1]);

    expect($body)->toBe(['ok' => true]);
    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->url() === 'https://mixtend.test/api/schedule.json?week=1'
        && $request->hasHeader('User-Agent', 'Mixtend Coding Test'));
});

test('非2xx では完全な URL とステータスを持つ MixtendHttpException を投げる', function () {
    Http::fake(['https://mixtend.test/api/schedule.json' => Http::response('Not Found', 404)]);

    expect(fn () => app(SendMixtendRequest::class)->run(MixtendRoute::Schedule))
        ->toThrow(fn (MixtendHttpException $e) => expect($e->context())->toBe([
            'url' => 'https://mixtend.test/api/schedule.json',
            'status' => 404,
        ]));
});
