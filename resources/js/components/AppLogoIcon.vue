<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        :class="className"
        v-bind="$attrs"
    >
        <rect x="11.1" y="8.5" width="1.8" height="13.5" rx="0.9" />
        <path
            d="M11.5 12.4C11.5 8.2 8.5 5 4.3 5C4 5 3.8 5.2 3.8 5.5C3.8 9.7 6.8 12.9 11 12.9C11.3 12.9 11.5 12.7 11.5 12.4Z"
        />
        <path
            d="M12.5 10.4C12.5 6.9 15.1 4 18.7 4C19 4 19.2 4.2 19.2 4.5C19.2 8 16.6 10.9 13 10.9C12.7 10.9 12.5 10.7 12.5 10.4Z"
        />
    </svg>
</template>
