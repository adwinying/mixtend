import '../../resources/css/app.css';

/** 本番は laravel-vite-plugin が bunny から取得したフォントを配信する（vite.config.ts の fonts）。VRT では同じ配信元を直接読み込む */
const fonts = document.createElement('link');
fonts.rel = 'stylesheet';
fonts.href =
    'https://fonts.bunny.net/css2?family=Noto+Sans:wght@400&family=Noto+Sans+JP:wght@400&display=swap';
await new Promise((resolve, reject) => {
    fonts.onload = resolve;
    fonts.onerror = reject;
    document.head.append(fonts);
});

/** resources/views/app.blade.php の body と揃える */
document.body.className = 'font-sans antialiased';
