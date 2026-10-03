<script setup lang="ts">
import { computed } from 'vue';
import type { ScheduleIndexResponseMeeting } from '@/generated/types';
import { GRID_LINE_PX, HOUR_HEIGHT_PX, toMinutes } from './grid';

const props = defineProps<{
    meeting: ScheduleIndexResponseMeeting;
    gridStart: string;
}>();

/** これより短いミーティングは、Figma の余白と文字サイズでは収まらないため詰めて表示する */
const COMPACT_BELOW_MINUTES = 45;

const durationMinutes = computed(
    () => toMinutes(props.meeting.end) - toMinutes(props.meeting.start),
);

const isCompact = computed(() => durationMinutes.value < COMPACT_BELOW_MINUTES);

/** 開始・終了時刻から、行にスナップせず分単位でブロックを配置する */
const style = computed(() => {
    const toPx = (minutes: number) => (minutes / 60) * HOUR_HEIGHT_PX;

    return {
        top: `${toPx(toMinutes(props.meeting.start) - toMinutes(props.gridStart))}px`,
        height: `${toPx(durationMinutes.value) - GRID_LINE_PX}px`,
    };
});
</script>

<template>
    <div
        class="absolute inset-x-0 truncate bg-primary px-5.25 text-white"
        :class="isCompact ? 'py-0.5 text-sm leading-4' : 'py-5'"
        :style="style"
        :title="meeting.summary"
    >
        {{ meeting.summary }}
        <span class="sr-only">{{ meeting.start }}〜{{ meeting.end }}</span>
    </div>
</template>
