<!-- レイアウトだけを担い、アイコンの色や大きさは呼び出し側が決める -->
<template>
    <div class="flex flex-col items-center gap-4">
        <slot name="icon" />
        <p class="text-center"><slot /></p>
    </div>
</template>
