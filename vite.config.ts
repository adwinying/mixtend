import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';
import { playwright } from 'vite-plus/test/browser-playwright';

/** VRT は Laravel を介さないため、public/hot を書き換える laravel() と artisan を呼ぶ wayfinder() を外す */
const isVitest = process.env.VITEST !== undefined;

// 描画は OS で変わるため、ベースラインと同じ Playwright の Docker イメージ（linux/arm64）でのみ実行する
if (isVitest && process.platform !== 'linux') {
    throw new Error('VRT は npm run test:vrt で Docker 上で実行してください');
}

/** Figma のフレームと同じデスクトップのサイズ。モバイルのデザインは提供されていないため、モバイルの VRT は省略する */
const vrtViewport = { width: 1440, height: 1473 };

export default defineConfig({
    plugins: lazyPlugins(() => [
        !isVitest &&
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
                refresh: true,
                fonts: [
                    // Figma は Noto Sans で、和文は Noto Sans JP にフォールバックする
                    bunny('Noto Sans', {
                        weights: [400],
                    }),
                    // 既定の subsets は latin のみで、和文が端末のフォントで描画されるため japanese を加える
                    bunny('Noto Sans JP', {
                        weights: [400],
                        subsets: ['latin', 'japanese'],
                    }),
                ],
            }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        !isVitest &&
            wayfinder({
                formVariants: true,
            }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    test: {
        include: ['resources/js/tests/**/*.test.ts'],
        setupFiles: ['resources/js/tests/setup.ts'],
        browser: {
            enabled: true,
            headless: true,
            // ページもフレームと同じサイズにし、iframe が縮小されないようにする
            provider: playwright({ contextOptions: { viewport: vrtViewport } }),
            viewport: vrtViewport,
            instances: [{ browser: 'chromium' }],
            expect: {
                toMatchScreenshot: {
                    screenshotOptions: {
                        animations: 'disabled',
                        caret: 'hide',
                    },
                },
            },
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
            'resources/js/generated/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/js/generated/**',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
