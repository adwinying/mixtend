<?php

namespace App\Actions\Mixtend;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * meetings は Y-m-d の日付をキー、ミーティングのリストを値とするマップ。
 * キーは Validator のルールで検証できず、array や list は空文字列の値を検証せずに通すため、implicit なルールで検証する。
 */
class MeetingsByDateRule implements ValidationRule
{
    /**
     * 空文字列でも検証する。
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail("{$attribute} が日付をキーとするマップではありません。");

            return;
        }

        foreach ($value as $date => $meetingsOfDay) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $date);
            if ($parsed === false || $parsed->format('Y-m-d') !== (string) $date) {
                $fail("{$attribute} のキー {$date} が Y-m-d 形式の日付ではありません。");
            }
            if (! is_array($meetingsOfDay) || ! array_is_list($meetingsOfDay)) {
                $fail("{$attribute}.{$date} がミーティングのリストではありません。");
            }
        }
    }
}
