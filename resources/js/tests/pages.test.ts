import { afterEach, expect, test, vi } from 'vite-plus/test';
import { page } from 'vite-plus/test/browser';
import { createApp } from 'vue';
import type { App, Component } from 'vue';
// @ エイリアスは laravel() が定義するため、VRT では相対パスで読み込む
import ErrorPage from '../pages/error.vue';
import ScheduleIndex from '../pages/schedule-index.vue';
import type {
    ErrorResponse,
    ScheduleIndexResponse,
    ScheduleIndexResponseDay,
} from '../generated/types';

/** Head は createInertiaApp の初期化に依存するため、Inertia を介さずにマウントできるよう外す */
vi.mock('@inertiajs/vue3', () => ({ Head: () => null }));

let app: App | undefined;

afterEach(() => {
    app?.unmount();
    document.body.replaceChildren();
});

/** document.fonts.ready は読み込み開始前だと即座に解決するため、レイアウトを確定させてフォントの読み込みを始めさせてから待つ */
const mountPage = async (
    component: Component,
    props: Record<string, unknown>,
) => {
    app = createApp(component, props);
    app.mount(document.body.appendChild(document.createElement('div')));
    document.body.getBoundingClientRect();
    await document.fonts.ready;
};

const hours = [
    '10:00',
    '11:00',
    '12:00',
    '13:00',
    '14:00',
    '15:00',
    '16:00',
    '17:00',
    '18:00',
    '19:00',
];

/** スクリーンショット名は日本語だとファイル名から除かれるため英字にする */
const scheduleStates = {
    /** API サンプル（schedule.json）を GetScheduleService で正規化した props */
    'api-sample': {
        hours,
        days: [
            {
                date: '2021-03-22',
                meetings: [
                    { summary: 'Meeting 1', start: '10:00', end: '11:00' },
                ],
            },
            {
                date: '2021-03-23',
                meetings: [
                    { summary: 'Meeting 2', start: '14:00', end: '15:00' },
                    { summary: 'Meeting 3', start: '16:00', end: '17:00' },
                ],
            },
            {
                date: '2021-03-24',
                meetings: [
                    { summary: 'Meeting 4', start: '10:30', end: '11:30' },
                ],
            },
        ],
    },
    'no-meetings': { hours, days: [] },
    'many-days-scroll': {
        hours,
        days: Array.from(
            { length: 7 },
            (_, index): ScheduleIndexResponseDay => ({
                date: `2021-03-${22 + index}`,
                meetings: [
                    {
                        summary: `Meeting ${index + 1}`,
                        start: `${10 + index}:00`,
                        end: `${11 + index}:00`,
                    },
                ],
            }),
        ),
    },
    'long-summary-short-meetings': {
        hours,
        days: [
            {
                date: '2021-03-22',
                meetings: [
                    {
                        summary:
                            '四半期の事業計画レビューと来期の採用計画に関する定例ミーティング',
                        start: '10:00',
                        end: '11:00',
                    },
                    { summary: '15分の朝会', start: '13:00', end: '13:15' },
                    { summary: '30分の 1on1', start: '14:00', end: '14:30' },
                ],
            },
        ],
    },
} satisfies Record<string, ScheduleIndexResponse>;

test.each(Object.entries(scheduleStates))('%s', async (name, props) => {
    await mountPage(ScheduleIndex, props);

    await expect
        .element(page.elementLocator(document.body))
        .toMatchScreenshot(name);
});

test('error-502', async () => {
    await mountPage(ErrorPage, {
        status: 502,
        detail: null,
    } satisfies ErrorResponse);

    await expect
        .element(page.elementLocator(document.body))
        .toMatchScreenshot('error-502');
});
