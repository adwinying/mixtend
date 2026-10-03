import { expect, test } from 'vite-plus/test';
import { page } from 'vite-plus/test/browser';
import type {
    ScheduleIndexResponse,
    ScheduleIndexResponseDay,
} from '@/generated/types';
import SchedulePageIndex from '@/features/schedule/page-index.vue';
import { mountPage } from '@/tests/mount-page';

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
    await mountPage(SchedulePageIndex, props);

    await expect
        .element(page.elementLocator(document.body))
        .toMatchScreenshot(name);
});
