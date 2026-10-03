<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

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
