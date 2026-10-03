<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['inertia.ssr.enabled' => false]);
    Http::preventStrayRequests();
});

test('日付順の days と勤務時間の各正時の hours を props として描画する', function () {
    Http::fake([config('services.schedule.url') => Http::response(scheduleApiSample())]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('schedule-index')
            ->where('days', [
                ['date' => '2021-03-22', 'meetings' => [
                    ['summary' => 'Meeting 1', 'start' => '10:00', 'end' => '11:00'],
                ]],
                ['date' => '2021-03-23', 'meetings' => [
                    ['summary' => 'Meeting 2', 'start' => '14:00', 'end' => '15:00'],
                    ['summary' => 'Meeting 3', 'start' => '16:00', 'end' => '17:00'],
                ]],
                ['date' => '2021-03-24', 'meetings' => [
                    ['summary' => 'Meeting 4', 'start' => '10:30', 'end' => '11:30'],
                ]],
            ])
            ->where('hours', ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'])
        );
});

test('User-Agent を Mixtend Coding Test にして API を呼び出す', function () {
    Http::fake([config('services.schedule.url') => Http::response(scheduleApiSample())]);

    $this->get(route('home'))->assertOk();

    Http::assertSent(fn (Request $request) => $request->url() === config('services.schedule.url')
        && $request->hasHeader('User-Agent', 'Mixtend Coding Test'));
});

test('API のレスポンスを schedule チャネルに記録する', function () {
    Http::fake([config('services.schedule.url') => Http::response(scheduleApiSample())]);

    $logPath = tempnam(sys_get_temp_dir(), 'schedule-log');
    config(['logging.channels.schedule.path' => $logPath]);

    $this->get(route('home'))->assertOk();

    $entry = json_decode((string) file_get_contents($logPath), true);
    expect($entry['datetime'])->not->toBeEmpty()
        ->and($entry['context'])->toBe([
            'url' => config('services.schedule.url'),
            'status' => 200,
            'body' => scheduleApiSample(),
        ]);

    unlink($logPath);
});
