<script setup lang="ts">
import SchedulePageIndex from '@/features/schedule/page-index.vue';
import type { ScheduleIndexResponse } from '@/generated/types';

defineProps<ScheduleIndexResponse>();
</script>

<template>
    <SchedulePageIndex :hours="hours" :days="days" />
</template>
