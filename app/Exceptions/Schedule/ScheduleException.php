<?php

namespace App\Exceptions\Schedule;

use RuntimeException;
use Throwable;

/**
 * スケジュール API の障害（接続失敗・非2xx・不正な JSON・検証エラー）を表す。
 * 報告は Laravel のデフォルトの reporter に任せ、context() の内容がログに添えられる。
 */
class ScheduleException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context  URL・ステータス・検証エラーなど
     */
    public function __construct(string $message, private readonly array $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
