<?php

namespace App\Exceptions;

/**
 * Mixtend との通信の失敗（接続失敗・タイムアウト・非2xx・不正な JSON・JSON オブジェクト以外のレスポンス）を表す。
 */
class MixtendHttpException extends MixtendException {}
