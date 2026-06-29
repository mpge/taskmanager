<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        entries: string[];
        color: string;
        today: string; // 'Y-m-d'
        weeks?: number;
    }>(),
    { weeks: 20 },
);

const PITCH = 15; // 12px cell + 3px gap

function pad(n: number): string {
    return n < 10 ? `0${n}` : `${n}`;
}

function toYmd(date: Date): string {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function parseYmd(value: string): Date {
    const [y, m, d] = value.split('-').map(Number);

    return new Date(y, m - 1, d);
}

function addDays(date: Date, amount: number): Date {
    const copy = new Date(date);
    copy.setDate(copy.getDate() + amount);

    return copy;
}

function startOfWeekMonday(date: Date): Date {
    const weekday = (date.getDay() + 6) % 7; // Mon = 0 … Sun = 6

    return addDays(date, -weekday);
}

interface Cell {
    ymd: string;
    done: boolean;
    future: boolean;
}

const doneSet = computed(() => new Set(props.entries));

const columns = computed<{ start: Date; days: Cell[] }[]>(() => {
    const today = parseYmd(props.today);
    const lastColumnStart = startOfWeekMonday(today);
    const firstColumnStart = addDays(lastColumnStart, -(props.weeks - 1) * 7);

    const result: { start: Date; days: Cell[] }[] = [];

    for (let w = 0; w < props.weeks; w++) {
        const columnStart = addDays(firstColumnStart, w * 7);
        const days: Cell[] = [];

        for (let d = 0; d < 7; d++) {
            const date = addDays(columnStart, d);
            const ymd = toYmd(date);
            days.push({
                ymd,
                done: doneSet.value.has(ymd),
                future: date > today,
            });
        }

        result.push({ start: columnStart, days });
    }

    return result;
});

const monthLabels = computed<{ index: number; text: string }[]>(() => {
    const labels: { index: number; text: string }[] = [];
    let lastMonth = -1;

    columns.value.forEach((column, index) => {
        const month = column.start.getMonth();

        if (month !== lastMonth) {
            labels.push({
                index,
                text: column.start.toLocaleString('en', { month: 'short' }),
            });
            lastMonth = month;
        }
    });

    return labels;
});

function cellStyle(cell: Cell): Record<string, string> {
    if (cell.done) {
        return { backgroundColor: props.color };
    }

    if (cell.future) {
        return { backgroundColor: 'var(--growth-0)', opacity: '0.35' };
    }

    return { backgroundColor: 'var(--growth-0)' };
}
</script>

<template>
    <div class="inline-flex flex-col gap-1">
        <div
            class="relative h-3 text-[10px] text-muted-foreground"
            :style="{ width: `${weeks * PITCH}px` }"
        >
            <span
                v-for="label in monthLabels"
                :key="label.index"
                class="absolute top-0"
                :style="{ left: `${label.index * PITCH}px` }"
                >{{ label.text }}</span
            >
        </div>
        <div class="flex gap-[3px]">
            <div
                v-for="(column, ci) in columns"
                :key="ci"
                class="flex flex-col gap-[3px]"
            >
                <div
                    v-for="cell in column.days"
                    :key="cell.ymd"
                    class="size-3 rounded-[3px] ring-1 ring-black/[0.03]"
                    :style="cellStyle(cell)"
                    :title="cell.ymd"
                ></div>
            </div>
        </div>
    </div>
</template>
