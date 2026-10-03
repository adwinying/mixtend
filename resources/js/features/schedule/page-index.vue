<script setup lang="ts">
import { CalendarX } from '@lucide/vue';
import dayjs from 'dayjs';
import 'dayjs/locale/ja';
import CommonEmpty from '@/components/common/empty.vue';
import CommonLayout from '@/components/common/layout.vue';
import type { ScheduleIndexResponse } from '@/generated/types';
import ScheduleDayColumn from '@/features/schedule/day-column.vue';
import ScheduleHourAxis from '@/features/schedule/hour-axis.vue';

defineProps<ScheduleIndexResponse>();

const formatDate = (date: string) =>
    dayjs(date).locale('ja').format('M/D（ddd）');
</script>

<template>
    <CommonLayout
        title="カレンダーUI"
        class="px-2 py-6 text-2xl leading-[normal] sm:px-4 sm:py-15"
    >
        <div class="mx-auto w-fit max-w-full">
            <h1 class="leading-8.75">カレンダーUI</h1>
            <!-- バックエンドはミーティングのある日付だけを返すため、days が空ならミーティングは 0 件 -->
            <CommonEmpty v-if="days.length === 0" class="mt-6 sm:mt-13.25">
                <template #icon>
                    <CalendarX class="size-12 text-line" aria-hidden="true" />
                </template>
                ミーティングはありません
            </CommonEmpty>
            <!-- 列幅は固定し、収まらない分は横スクロールにする -->
            <div v-else class="mt-6 overflow-x-auto sm:mt-13.25">
                <div class="flex w-max border-t-2 border-line">
                    <ScheduleHourAxis :hours="hours" />
                    <ScheduleDayColumn
                        v-for="{ date, meetings } in days"
                        :key="date"
                        :heading="formatDate(date)"
                        :hours="hours"
                        :meetings="meetings"
                    />
                </div>
            </div>
        </div>
    </CommonLayout>
</template>
