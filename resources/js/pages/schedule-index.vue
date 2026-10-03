<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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

const toMinutes = (time: string) => {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
};

const formatDate = (date: string) =>
    dayjs(date).locale('ja').format('M/D（ddd）');

/** 開始・終了時刻から、行にスナップせず分単位でブロックを配置する */
const meetingStyle = ({ start, end }: ScheduleIndexResponseMeeting) => {
    const toPx = (minutes: number) => (minutes / 60) * HOUR_HEIGHT_PX;
    const gridStart = toMinutes(props.hours[0]);

    return {
        top: `${toPx(toMinutes(start) - gridStart)}px`,
        height: `${toPx(toMinutes(end) - toMinutes(start)) - GRID_LINE_PX}px`,
    };
};
</script>

<template>
    <main
        class="min-h-screen bg-white px-4 py-15 text-2xl leading-[normal] text-foreground"
    >
        <Head title="カレンダーUI" />
        <div class="mx-auto w-fit">
            <h1 class="leading-8.75">カレンダーUI</h1>
            <div class="mt-13.25 flex border-t-2 border-l-2 border-line">
                <div class="w-45.75 border-r-2 border-line">
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
                    v-for="day in days"
                    :key="day.date"
                    class="w-76 border-r-2 border-line"
                >
                    <div
                        class="flex h-18.75 items-center justify-center border-b-2 border-line"
                    >
                        {{ formatDate(day.date) }}
                    </div>
                    <div class="relative">
                        <div
                            v-for="hour in hours"
                            :key="hour"
                            class="border-b-2 border-line"
                            :style="{ height: `${HOUR_HEIGHT_PX}px` }"
                        />
                        <div
                            v-for="meeting in day.meetings"
                            :key="`${meeting.start}-${meeting.summary}`"
                            class="absolute inset-x-0 bg-primary px-5.25 py-5 text-white"
                            :style="meetingStyle(meeting)"
                        >
                            {{ meeting.summary }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>
