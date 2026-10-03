import { afterEach } from 'vite-plus/test';
import { createApp } from 'vue';
import type { App, Component } from 'vue';

let app: App | undefined;

afterEach(() => {
    app?.unmount();
    document.body.replaceChildren();
});

/** document.fonts.ready は読み込み開始前だと即座に解決するため、レイアウトを確定させてフォントの読み込みを始めさせてから待つ */
export const mountPage = async (
    component: Component,
    props: Record<string, unknown>,
) => {
    app = createApp(component, props);
    app.mount(document.body.appendChild(document.createElement('div')));
    document.body.getBoundingClientRect();
    await document.fonts.ready;
};
