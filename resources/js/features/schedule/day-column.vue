<script setup lang="ts">
import type { ScheduleIndexResponseMeeting } from '@/generated/types';
import { HOUR_HEIGHT_PX } from '@/features/schedule/grid';
import ScheduleMeetingBlock from '@/features/schedule/meeting-block.vue';

defineProps<{
    heading: string;
    hours: string[];
    meetings: ScheduleIndexResponseMeeting[];
}>();
</script>

<template>
    <div class="w-76 border-r-2 border-line">
        <div
            class="flex h-18.75 items-center justify-center border-b-2 border-line"
        >
            {{ heading }}
        </div>
        <div class="relative">
            <div
                v-for="hour in hours"
                :key="hour"
                class="border-b-2 border-line"
                :style="{ height: `${HOUR_HEIGHT_PX}px` }"
            />
            <ScheduleMeetingBlock
                v-for="meeting in meetings"
                :key="`${meeting.start}-${meeting.summary}`"
                :meeting="meeting"
                :grid-start="hours[0]"
            />
        </div>
    </div>
</template>
