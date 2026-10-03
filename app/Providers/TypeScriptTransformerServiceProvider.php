<?php

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\LaravelData\LaravelDataTypeScriptTransformerExtension;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter;

class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    /**
     * laravel-data の Data クラスを `resources/js/generated/types.ts` にモジュールの型として出力する。
     * グローバル名前空間（`App.*`）の入れ子の namespace は Vue の defineProps が型を解決できないため使わない。
     * 出力ファイルは1つだけなので、古いファイル削除用のマニフェストは生成しない。
     */
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->extension(new LaravelDataTypeScriptTransformerExtension)
            ->transformDirectories(app_path())
            ->writer(new FlatModuleWriter('types.ts'))
            ->withoutManifest();
    }
}
