<?php

namespace App\Exceptions\Mixtend;

use RuntimeException;
use Throwable;

/**
 * Mixtend 由来の失敗の基底。種類はサブクラスで区別し、捕捉はこのクラスでまとめて行う。
 * 報告は Laravel のデフォルトの reporter に任せ、context() の内容がログに添えられる。
 */
abstract class MixtendException extends RuntimeException
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
