<?php

use App\Exceptions\MixtendHttpException;
use App\Exceptions\MixtendScheduleException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['inertia.ssr.enabled' => false]);
    Http::preventStrayRequests();
});

function mixtendScheduleUrl(): string
{
    return config()->string('services.mixtend.base_url').'/schedule.json';
}

/**
 * Mixtend のスケジュールのドキュメントにあるサンプルレスポンス。
 * 日付キーの並び替えを検証できるよう、順序をあえて崩している。
 *
 * @return array{working_hours: array{start: string, end: string}, meetings: array<string, list<array{summary: string, start: string, end: string, timezone: string}>>}
 */
function scheduleApiSample(): array
{
    return [
        'working_hours' => ['start' => '10:00', 'end' => '19:00'],
        'meetings' => [
            '2021-03-24' => [['summary' => 'Meeting 4', 'start' => '10:30', 'end' => '11:30', 'timezone' => 'Asia/Tokyo']],
            '2021-03-22' => [['summary' => 'Meeting 1', 'start' => '10:00', 'end' => '11:00', 'timezone' => 'Asia/Tokyo']],
            '2021-03-23' => [
                ['summary' => 'Meeting 2', 'start' => '14:00', 'end' => '15:00', 'timezone' => 'Asia/Tokyo'],
                ['summary' => 'Meeting 3', 'start' => '16:00', 'end' => '17:00', 'timezone' => 'Asia/Tokyo'],
            ],
        ],
    ];
}

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

/**
 * @return ArrayObject<int, array<mixed>> 記録された warning のコンテキスト
 */
function recordLogWarnings(): ArrayObject
{
    $warnings = new ArrayObject;
    Log::listen(function (MessageLogged $log) use ($warnings) {
        if ($log->level === 'warning') {
            $warnings[] = $log->context;
        }
    });

    return $warnings;
}

test('API が利用できないときは 502 でエラーページを描画する', function (mixed $fakeResponse) {
    config(['app.debug' => false]);
    Http::fake([mixtendScheduleUrl() => $fakeResponse]);

    $this->get(route('home'))
        ->assertStatus(502)
        ->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 502)
            ->where('detail', null)
        );
})->with([
    '非2xx' => fn () => Http::response('Service Unavailable', 503),
    'リダイレクト先のない3xx' => fn () => Http::response(scheduleApiSample(), 302),
    'タイムアウト・接続失敗' => fn () => Http::failedConnection(),
    '不正な JSON' => fn () => Http::response('{"working_hours":', 200),
    'JSON がオブジェクトではない' => fn () => Http::response('123', 200),
    'working_hours の欠落' => fn () => Http::response(scheduleApiSampleWith(['working_hours' => null])),
    'meetings の欠落' => fn () => Http::response(scheduleApiSampleWith(['meetings' => null])),
    'meetings が空文字列' => fn () => Http::response(scheduleApiSampleWith(['meetings' => ''])),
    '日付の値が空文字列' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22' => ''])),
    'summary の欠落' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.summary' => null])),
    '勤務時間の形式違い' => fn () => Http::response(scheduleApiSampleWith(['working_hours.start' => '10時'])),
    '勤務時間の終了が開始と同時刻' => fn () => Http::response(scheduleApiSampleWith(['working_hours.end' => '10:00'])),
    '勤務時間の終了が開始より前' => fn () => Http::response(scheduleApiSampleWith(['working_hours.end' => '09:59'])),
    '勤務時間の開始が配列' => fn () => Http::response(scheduleApiSampleWith(['working_hours.start' => ['10:00']])),
    'ミーティングの時刻の形式違い' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.end' => '11:00:00'])),
    'ミーティングの終了が開始と同時刻' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.end' => '10:00'])),
    'ミーティングの終了が開始より前' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.end' => '09:59'])),
    'ミーティングの開始が配列' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.start' => ['10:00']])),
    '不正なタイムゾーン' => fn () => Http::response(scheduleApiSampleWith(['meetings.2021-03-22.0.timezone' => 'Asia/Nowhere'])),
    '日付キーの形式違い' => fn () => Http::response(scheduleApiSampleWith(['meetings' => ['2021/03/22' => []]])),
]);

test('API の障害をコンテキスト付きで1度だけ報告する', function () {
    Exceptions::fake();
    Http::fake([mixtendScheduleUrl() => Http::response('Service Unavailable', 503)]);

    $this->get(route('home'))->assertStatus(502);

    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(fn (MixtendHttpException $e) => $e->context() === ['url' => mixtendScheduleUrl(), 'status' => 503]
        && $e->getPrevious() instanceof RequestException);
});

test('API の検証エラーをコンテキストとして報告する', function () {
    Exceptions::fake();
    Http::fake([mixtendScheduleUrl() => Http::response(scheduleApiSampleWith(['working_hours.start' => '10時']))]);

    $this->get(route('home'))->assertStatus(502);

    Exceptions::assertReported(fn (MixtendScheduleException $e) => array_keys($e->context()['errors']) === ['working_hours.start']);
});

test('debug 有効時は API の障害の詳細をエラーページに表示する', function () {
    config(['app.debug' => true]);
    Http::fake([mixtendScheduleUrl() => Http::response('Service Unavailable', 503)]);

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
    Http::fake([mixtendScheduleUrl() => Http::response(scheduleApiSample())]);

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
    Http::fake([mixtendScheduleUrl() => Http::response(scheduleApiSample())]);

    $this->get(route('home'))->assertOk();

    Http::assertSent(fn (Request $request) => $request->url() === mixtendScheduleUrl()
        && $request->hasHeader('User-Agent', 'Mixtend Coding Test'));
});

test('API のレスポンスを mixtend チャネルに記録する', function () {
    Http::fake([mixtendScheduleUrl() => Http::response(scheduleApiSample())]);

    $logPath = tempnam(sys_get_temp_dir(), 'mixtend-log');
    config(['logging.channels.mixtend.path' => $logPath]);

    $this->get(route('home'))->assertOk();

    $entry = json_decode((string) file_get_contents($logPath), true);
    expect($entry['datetime'])->not->toBeEmpty()
        ->and($entry['context'])->toBe([
            'url' => mixtendScheduleUrl(),
            'status' => 200,
            'body' => scheduleApiSample(),
        ]);

    unlink($logPath);
});

test('Tokyo 以外のタイムゾーンのミーティングを日本時間に変換し、変換後の日付と開始時刻の順に並べる', function () {
    Http::fake([mixtendScheduleUrl() => Http::response(scheduleApiSampleWith([
        // Honolulu（UTC-10）の 20:00 は翌日 15:00 の日本時間になり、2021-03-22 の列は無くなる
        'meetings.2021-03-22.0' => ['summary' => 'Meeting 1', 'start' => '20:00', 'end' => '21:00', 'timezone' => 'Pacific/Honolulu'],
        // London（UTC+0）の 01:30 は同じ日の 10:30 の日本時間になる
        'meetings.2021-03-24.0' => ['summary' => 'Meeting 4', 'start' => '01:30', 'end' => '02:30', 'timezone' => 'Europe/London'],
    ]))]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('days', [
                ['date' => '2021-03-23', 'meetings' => [
                    ['summary' => 'Meeting 2', 'start' => '14:00', 'end' => '15:00'],
                    ['summary' => 'Meeting 1', 'start' => '15:00', 'end' => '16:00'],
                    ['summary' => 'Meeting 3', 'start' => '16:00', 'end' => '17:00'],
                ]],
                ['date' => '2021-03-24', 'meetings' => [
                    ['summary' => 'Meeting 4', 'start' => '10:30', 'end' => '11:30'],
                ]],
            ])
        );
});

test('同じ日のミーティングが重なると warning を記録し、props はそのまま描画する', function () {
    $warnings = recordLogWarnings();
    Http::fake([mixtendScheduleUrl() => Http::response(scheduleApiSampleWith([
        // 並べ替えると先頭になり、隣り合わない Meeting 3 とも重なる
        'meetings.2021-03-23.2' => ['summary' => 'Meeting 5', 'start' => '13:00', 'end' => '16:30', 'timezone' => 'Asia/Tokyo'],
    ]))]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.1.meetings', [
                ['summary' => 'Meeting 5', 'start' => '13:00', 'end' => '16:30'],
                ['summary' => 'Meeting 2', 'start' => '14:00', 'end' => '15:00'],
                ['summary' => 'Meeting 3', 'start' => '16:00', 'end' => '17:00'],
            ])
        );

    expect($warnings->getArrayCopy())->toBe([
        [
            'date' => '2021-03-23',
            'previous' => ['summary' => 'Meeting 5', 'start' => '13:00', 'end' => '16:30'],
            'next' => ['summary' => 'Meeting 2', 'start' => '14:00', 'end' => '15:00'],
        ],
        [
            'date' => '2021-03-23',
            'previous' => ['summary' => 'Meeting 5', 'start' => '13:00', 'end' => '16:30'],
            'next' => ['summary' => 'Meeting 3', 'start' => '16:00', 'end' => '17:00'],
        ],
    ]);
});

test('終了と開始が同時刻の連続するミーティングでは warning を記録しない', function () {
    $warnings = recordLogWarnings();
    Http::fake([mixtendScheduleUrl() => Http::response(scheduleApiSampleWith([
        'meetings.2021-03-23.1.start' => '15:00',
    ]))]);

    $this->get(route('home'))->assertOk();

    expect($warnings->getArrayCopy())->toBe([]);
});

test('予定が0件のときは空の days と勤務時間の hours を props として描画する', function () {
    Http::fake([mixtendScheduleUrl() => Http::response(scheduleApiSampleWith(['meetings' => new stdClass]))]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('schedule-index')
            ->where('days', [])
            ->has('hours', 10)
        );
});
