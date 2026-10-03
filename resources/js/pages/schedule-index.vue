<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/ja';
import type {
    ScheduleIndexResponse,
    ScheduleIndexResponseMeeting,
} from '@/generated/types';

const props = defineProps<ScheduleIndexResponse>();

/** 1時間分の行の高さ（px）。グリッド線を含む */
const HOUR_HEIGHT_PX = 110;
/** グリッド線の太さ（px）。ブロックが次の行の線に重ならないよう高さから引く */
const GRID_LINE_PX = 2;
/** これより短いミーティングは、Figma の余白と文字サイズでは収まらないため詰めて表示する */
const COMPACT_BELOW_MINUTES = 45;

const toMinutes = (time: string) => {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
};

const formatDate = (date: string) =>
    dayjs(date).locale('ja').format('M/D（ddd）');

const durationMinutes = ({ start, end }: ScheduleIndexResponseMeeting) =>
    toMinutes(end) - toMinutes(start);

const isCompact = (meeting: ScheduleIndexResponseMeeting) =>
    durationMinutes(meeting) < COMPACT_BELOW_MINUTES;

/** 予定が0件でもグリッドを表示するため、空の列に「予定はありません」を出す */
const columns = computed(() =>
    props.days.length > 0
        ? props.days.map(({ date, meetings }) => ({
              key: date,
              heading: formatDate(date),
              meetings,
          }))
        : [{ key: 'empty', heading: '予定はありません', meetings: [] }],
);

/** 開始・終了時刻から、行にスナップせず分単位でブロックを配置する */
const meetingStyle = (meeting: ScheduleIndexResponseMeeting) => {
    const toPx = (minutes: number) => (minutes / 60) * HOUR_HEIGHT_PX;
    const gridStart = toMinutes(props.hours[0]);

    return {
        top: `${toPx(toMinutes(meeting.start) - gridStart)}px`,
        height: `${toPx(durationMinutes(meeting)) - GRID_LINE_PX}px`,
    };
};
</script>

<template>
    <main
        class="min-h-screen bg-white px-2 py-6 text-2xl leading-[normal] text-foreground sm:px-4 sm:py-15"
    >
        <Head title="カレンダーUI" />
        <div class="mx-auto w-fit max-w-full">
            <h1 class="leading-8.75">カレンダーUI</h1>
            <!-- 列幅は固定し、収まらない分は横スクロールにする -->
            <div class="mt-6 overflow-x-auto sm:mt-13.25">
                <div class="flex w-max border-t-2 border-line">
                    <div
                        class="sticky left-0 z-10 w-20 border-x-2 border-line bg-white sm:w-45.75"
                    >
                        <div class="h-18.75 border-b-2 border-line" />
                        <div
                            v-for="hour in hours"
                            :key="hour"
                            class="border-b-2 border-line pt-1.5 text-center"
                            :style="{ height: `${HOUR_HEIGHT_PX}px` }"
                        >
                            {{ hour }}
                        </div>
                    </div>
                    <div
                        v-for="column in columns"
                        :key="column.key"
                        class="w-76 border-r-2 border-line"
                    >
                        <div
                            class="flex h-18.75 items-center justify-center border-b-2 border-line"
                        >
                            {{ column.heading }}
                        </div>
                        <div class="relative">
                            <div
                                v-for="hour in hours"
                                :key="hour"
                                class="border-b-2 border-line"
                                :style="{ height: `${HOUR_HEIGHT_PX}px` }"
                            />
                            <div
                                v-for="meeting in column.meetings"
                                :key="`${meeting.start}-${meeting.summary}`"
                                class="absolute inset-x-0 truncate bg-primary px-5.25 text-white"
                                :class="
                                    isCompact(meeting)
                                        ? 'py-0.5 text-sm leading-4'
                                        : 'py-5'
                                "
                                :style="meetingStyle(meeting)"
                                :title="meeting.summary"
                            >
                                {{ meeting.summary }}
                                <span class="sr-only">
                                    {{ meeting.start }}〜{{ meeting.end }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>
