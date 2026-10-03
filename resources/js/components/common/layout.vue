<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

/** 余白や配置はページごとに異なるため、呼び出し側が class で足す */
defineProps<{ title: string }>();
</script>

<template>
    <main class="min-h-screen bg-white text-foreground">
        <Head :title="title" />
        <slot />
    </main>
</template>
