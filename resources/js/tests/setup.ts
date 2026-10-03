import { vi } from 'vite-plus/test';
import '../../css/app.css';
import './fonts/fonts.css';

/** Head は createInertiaApp の初期化に依存するため、Inertia を介さずにマウントできるよう外す */
vi.mock('@inertiajs/vue3', () => ({ Head: () => null }));

/** resources/views/app.blade.php の body と揃える */
document.body.className = 'font-sans antialiased';
