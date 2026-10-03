<?php

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\LaravelData\LaravelDataTypeScriptTransformerExtension;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;

class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    /**
     * laravel-data の Data クラスを、親クラス既定の `resources/js/generated/types.d.ts` に
     * グローバル名前空間（`App.*`）の型として出力する。
     * 出力ファイルは1つだけなので、古いファイル削除用のマニフェストは生成しない。
     */
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->extension(new LaravelDataTypeScriptTransformerExtension)
            ->transformDirectories(app_path())
            ->withoutManifest();
    }
}
