<script setup lang="ts">
import { Check } from '@lucide/vue';

withDefaults(
    defineProps<{
        done: boolean;
        color: string;
        size?: number;
    }>(),
    { size: 44 },
);

defineEmits<{ toggle: [] }>();
</script>

<template>
    <button
        type="button"
        :aria-pressed="done"
        class="flex shrink-0 items-center justify-center rounded-full border-2 transition-all duration-150 hover:scale-105 active:scale-90"
        :style="{
            width: `${size}px`,
            height: `${size}px`,
            borderColor: color,
            backgroundColor: done
                ? color
                : `color-mix(in oklab, ${color} 8%, transparent)`,
        }"
        @click="$emit('toggle')"
    >
        <Check
            class="text-white transition-transform duration-150"
            :class="done ? 'scale-100' : 'scale-0'"
            :style="{ width: `${size * 0.46}px`, height: `${size * 0.46}px` }"
        />
    </button>
</template>
