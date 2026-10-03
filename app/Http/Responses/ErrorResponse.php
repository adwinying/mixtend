<?php

namespace App\Http\Responses;

use App\Exceptions\Mixtend\MixtendException;
use Spatie\LaravelData\Data;
use Throwable;

class ErrorResponse extends Data
{
    /**
     * @param  string|null  $detail  debug 有効時のみ、調査用に例外のクラス・メッセージ・コンテキストを入れる
     */
    public function __construct(
        public int $status,
        public ?string $detail = null,
    ) {}

    public static function fromException(int $status, Throwable $exception): self
    {
        if (! config('app.debug')) {
            return new self($status);
        }

        $detail = $exception::class.': '.$exception->getMessage();
        if ($exception instanceof MixtendException) {
            $detail .= "\n".json_encode($exception->context(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return new self($status, $detail);
    }
}
