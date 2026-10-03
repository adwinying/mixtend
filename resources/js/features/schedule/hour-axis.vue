<script setup lang="ts">
import { HOUR_HEIGHT_PX } from './grid';

defineProps<{ hours: string[] }>();
</script>

<template>
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
</template>
