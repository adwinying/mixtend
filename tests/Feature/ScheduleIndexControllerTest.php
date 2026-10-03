<?php

use App\Exceptions\Schedule\ScheduleException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['inertia.ssr.enabled' => false]);
    Http::preventStrayRequests();
});

/**
 * @param  array<string, mixed>  $overrides  ドット記法で上書きする。null を渡すとキーを削除する
 * @return array<mixed>
 */
function scheduleApiSampleWith(array $overrides): array
{
    $schedule = scheduleApiSample();
    foreach ($overrides as $key => $value) {
        $value === null ? data_forget($schedule, $key) : data_set($schedule, $key, $value);
    }

    return $schedule;
}

test('API が利用できないときは 502 でエラーページを描画する', function (mixed $fakeResponse) {
    config(['app.debug' => false]);
    Http::fake([config('services.schedule.url') => $fakeResponse]);

    $this->get(route('home'))
        ->assertStatus(502)
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 502)
            ->where('detail', null)
        );
})->with([
    '非2xx' => fn () => Http::response('Service Unavailable', 503),
    'タイムアウト・接続失敗' => fn () => Http::failedConnection(),
    '不正な JSON' => fn () => Http::response('{"working_hours":', 200),
    'JSON がオブジェクトではない' => fn () => Http::response('123', 200),
    'working_hours の欠落' => fn () => Http::response(scheduleApiSampleWith(['working_hours' => null])),
    'meetings の欠落' => fn () => Http::response(scheduleApiSampleWith(['meetings' => null])),
    'summary の欠落' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.summary' => null])),
    '勤務時間の形式違い' => fn () => Http::response(scheduleApiSampleWith(['working_hours.start' => '10時'])),
    'ミーティングの時刻の形式違い' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.end' => '11:00:00'])),
    '不正なタイムゾーン' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.timezone' => 'Asia/Nowhere'])),
    '日付キーの形式違い' => fn () => Http::response(scheduleApiSampleWith(['meetings' => ['2021/03/22' => []]])),
]);

test('API の障害をコンテキスト付きで1度だけ報告する', function () {
    Exceptions::fake();
    Http::fake([config('services.schedule.url') => Http::response('Service Unavailable', 503)]);

    $this->get(route('home'))->assertStatus(502);

    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(fn (ScheduleException $e) => $e->context() === ['url' => config('services.schedule.url'), 'status' => 503]
        && $e->getPrevious() instanceof RequestException);
});

test('API の検証エラーをコンテキストとして報告する', function () {
    Exceptions::fake();
    Http::fake([config('services.schedule.url') => Http::response(scheduleApiSampleWith(['working_hours.start' => '10時']))]);

    $this->get(route('home'))->assertStatus(502);

    Exceptions::assertReported(fn (ScheduleException $e) => array_keys($e->context()['errors']) === ['working_hours.start']);
});

test('debug 有効時は API の障害の詳細をエラーページに表示する', function () {
    config(['app.debug' => true]);
    Http::fake([config('services.schedule.url') => Http::response('Service Unavailable', 503)]);

    $this->get(route('home'))
        ->assertStatus(502)
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('detail', fn (string $detail) => str_contains($detail, '"status": 503'))
        );
});

test('API 以外の想定外の例外では 500 でエラーページを描画する', function () {
    config(['app.debug' => false]);
    Http::fake(fn () => throw new RuntimeException('想定外のエラー'));

    $this->get(route('home'))
        ->assertInternalServerError()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 500)
            ->where('detail', null)
        );
});

test('存在しないパスでは 404 でエラーページを描画する', function () {
    config(['app.debug' => false]);

    $this->get('/not-found')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 404)
        );
});

test('debug 有効時は想定外の例外の詳細をエラーページに表示する', function () {
    config(['app.debug' => true]);
    Http::fake(fn () => throw new RuntimeException('想定外のエラー'));

    $this->get(route('home'))
        ->assertInternalServerError()
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 500)
            ->where('detail', fn (string $detail) => str_starts_with($detail, 'RuntimeException: 想定外のエラー'))
        );
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
