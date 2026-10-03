import { expect, test } from 'vite-plus/test';
import { page } from 'vite-plus/test/browser';
import type { ErrorResponse } from '@/generated/types';
import ErrorPage from '@/pages/error.vue';
import { mountPage } from '@/tests/mount-page';

test('error-502', async () => {
    await mountPage(ErrorPage, {
        status: 502,
        detail: null,
    } satisfies ErrorResponse);

    await expect
        .element(page.elementLocator(document.body))
        .toMatchScreenshot('error-502');
});
