<script setup lang="ts">
import { computed } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/ja';
import CommonLayout from '@/components/common/layout.vue';
import type { ScheduleIndexResponse } from '@/generated/types';
import ScheduleDayColumn from './day-column.vue';
import ScheduleHourAxis from './hour-axis.vue';

const props = defineProps<ScheduleIndexResponse>();

const formatDate = (date: string) =>
    dayjs(date).locale('ja').format('M/D（ddd）');

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
</script>

<template>
    <CommonLayout
        title="カレンダーUI"
        class="px-2 py-6 text-2xl leading-[normal] sm:px-4 sm:py-15"
    >
        <div class="mx-auto w-fit max-w-full">
            <h1 class="leading-8.75">カレンダーUI</h1>
            <!-- 列幅は固定し、収まらない分は横スクロールにする -->
            <div class="mt-6 overflow-x-auto sm:mt-13.25">
                <div class="flex w-max border-t-2 border-line">
                    <ScheduleHourAxis :hours="hours" />
                    <ScheduleDayColumn
                        v-for="column in columns"
                        :key="column.key"
                        :heading="column.heading"
                        :hours="hours"
                        :meetings="column.meetings"
                    />
                </div>
            </div>
        </div>
    </CommonLayout>
</template>
