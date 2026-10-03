<script setup lang="ts">
import { computed } from 'vue';
import CommonLayout from '@/components/common/layout.vue';
import type { ErrorResponse } from '@/generated/types';

const props = defineProps<ErrorResponse>();

const MESSAGES: Record<number, { title: string; description: string }> = {
    403: {
        title: 'アクセスできません',
        description: 'このページを表示する権限がありません。',
    },
    404: {
        title: 'ページが見つかりません',
        description: 'URL が正しいか確認してください。',
    },
    500: {
        title: 'エラーが発生しました',
        description: '時間をおいて再読み込みしてください。',
    },
    502: {
        title: 'スケジュールを取得できませんでした',
        description: '時間をおいて再読み込みしてください。',
    },
    503: {
        title: 'メンテナンス中です',
        description: '時間をおいて再読み込みしてください。',
    },
};

const message = computed(() => MESSAGES[props.status] ?? MESSAGES[500]);
</script>

<template>
    <CommonLayout
        :title="message.title"
        class="flex flex-col items-center justify-center px-4"
    >
        <p class="text-6xl font-bold text-primary">{{ status }}</p>
        <h1 class="mt-4 text-2xl">{{ message.title }}</h1>
        <p class="mt-2">{{ message.description }}</p>
        <pre
            v-if="detail"
            class="mt-8 max-w-full overflow-x-auto rounded bg-gray-100 p-4 text-left text-sm"
            >{{ detail }}</pre>
    </CommonLayout>
</template>
