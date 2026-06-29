<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ListTodo, Sparkles, Sprout } from '@lucide/vue';
import { computed } from 'vue';
import HabitToggle from '@/components/tend/HabitToggle.vue';
import InsightCard from '@/components/tend/InsightCard.vue';

interface TaskItem {
    id: number;
    title: string;
    notes: string | null;
    bucket: string;
    status: string;
    priority: number;
    due_date: string | null;
    completed_at: string | null;
    position: number;
}

interface HabitItem {
    id: number;
    name: string;
    cadence: string;
    color: string;
    current_streak: number;
    longest_streak: number;
    done_today: boolean;
}

interface InsightItem {
    type: string;
    tone: string;
    title: string;
    message: string;
}

const props = defineProps<{
    today: string;
    greetingName: string;
    insights: InsightItem[];
    focus: TaskItem[];
    habits: HabitItem[];
    stats: {
        important_open: number;
        eventual_open: number;
        habits_total: number;
        habits_done_today: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Today', href: '/dashboard' }],
    },
});

const firstName = computed(() => props.greetingName.split(' ')[0]);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

const prettyDate = computed(() =>
    new Date(`${props.today}T00:00:00`).toLocaleDateString('en', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    }),
);

function dueLabel(date: string | null): string | null {
    if (!date) {
        return null;
    }

    const due = new Date(`${date}T00:00:00`);
    const today = new Date(`${props.today.slice(0, 10)}T00:00:00`);
    const days = Math.round((due.getTime() - today.getTime()) / 86400000);

    if (days < 0) {
        return `${Math.abs(days)}d overdue`;
    }

    if (days === 0) {
        return 'Due today';
    }

    if (days === 1) {
        return 'Due tomorrow';
    }

    return `Due in ${days}d`;
}

function completeTask(id: number) {
    router.patch(`/tasks/${id}`, { status: 'done' }, { preserveScroll: true });
}

function toggleHabit(id: number) {
    router.post(`/habits/${id}/toggle`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Today" />

    <div class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
        <!-- Hero -->
        <header
            class="relative overflow-hidden rounded-3xl border border-border/60 p-6 sm:p-8"
            style="
                background-image: linear-gradient(
                    135deg,
                    color-mix(in oklab, var(--primary) 14%, var(--card)),
                    var(--card) 60%
                );
            "
        >
            <p class="text-sm font-medium text-muted-foreground">
                {{ prettyDate }}
            </p>
            <h1
                class="mt-1 font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl"
            >
                {{ greeting }}, {{ firstName }}.
            </h1>
            <div class="mt-5 flex flex-wrap gap-2.5">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-card/70 px-3 py-1.5 text-sm font-medium text-foreground ring-1 ring-border/70 backdrop-blur"
                >
                    <ListTodo class="size-4 text-primary" />
                    {{ stats.important_open }} important
                </span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-card/70 px-3 py-1.5 text-sm font-medium text-foreground ring-1 ring-border/70 backdrop-blur"
                >
                    <Sprout class="size-4" style="color: var(--success)" />
                    {{ stats.habits_done_today }}/{{ stats.habits_total }}
                    habits today
                </span>
            </div>
        </header>

        <!-- Insights -->
        <section v-if="insights.length" class="mt-6 grid gap-3 sm:grid-cols-2">
            <InsightCard
                v-for="(insight, i) in insights"
                :key="i"
                :insight="insight"
            />
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- Focus -->
            <section
                class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm sm:p-6"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h2
                        class="flex items-center gap-2 font-display text-lg font-semibold text-foreground"
                    >
                        <Sparkles class="size-5 text-primary" />
                        Focus
                    </h2>
                    <Link
                        href="/tasks"
                        class="inline-flex items-center gap-1 text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
                    >
                        All tasks
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <ul v-if="focus.length" class="flex flex-col gap-1">
                    <li
                        v-for="task in focus"
                        :key="task.id"
                        class="group flex items-center gap-3 rounded-2xl px-2 py-2 transition-colors hover:bg-accent/60"
                    >
                        <button
                            type="button"
                            class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-muted-foreground/40 transition-colors hover:border-success hover:bg-success/10"
                            aria-label="Complete task"
                            @click="completeTask(task.id)"
                        />
                        <span class="min-w-0 flex-1 truncate text-foreground">{{
                            task.title
                        }}</span>
                        <span
                            v-if="dueLabel(task.due_date)"
                            class="shrink-0 text-xs font-medium"
                            :class="
                                task.due_date &&
                                task.due_date < props.today.slice(0, 10)
                                    ? 'text-destructive'
                                    : 'text-muted-foreground'
                            "
                            >{{ dueLabel(task.due_date) }}</span
                        >
                    </li>
                </ul>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border py-10 text-center"
                >
                    <Sparkles class="size-7 text-muted-foreground/60" />
                    <p class="mt-2 text-sm text-muted-foreground">
                        Nothing urgent. Enjoy the clear runway.
                    </p>
                </div>
            </section>

            <!-- Today's habits -->
            <section
                class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm sm:p-6"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h2
                        class="flex items-center gap-2 font-display text-lg font-semibold text-foreground"
                    >
                        <Sprout class="size-5" style="color: var(--success)" />
                        Today's habits
                    </h2>
                    <Link
                        href="/habits"
                        class="inline-flex items-center gap-1 text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
                    >
                        All habits
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <ul v-if="habits.length" class="flex flex-col gap-2">
                    <li
                        v-for="habit in habits"
                        :key="habit.id"
                        class="flex items-center gap-3 rounded-2xl border border-border/50 p-2.5"
                    >
                        <HabitToggle
                            :done="habit.done_today"
                            :color="habit.color"
                            :size="40"
                            @toggle="toggleHabit(habit.id)"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-foreground">
                                {{ habit.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    habit.current_streak > 0
                                        ? `${habit.current_streak} ${habit.cadence === 'weekly' ? 'week' : 'day'} streak`
                                        : 'Start a streak today'
                                }}
                            </p>
                        </div>
                    </li>
                </ul>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border py-10 text-center"
                >
                    <Sprout class="size-7 text-muted-foreground/60" />
                    <p class="mt-2 text-sm text-muted-foreground">
                        No habits yet. Plant your first one.
                    </p>
                    <Link
                        href="/habits"
                        class="mt-3 text-sm font-medium text-primary hover:underline"
                        >Add a habit</Link
                    >
                </div>
            </section>
        </div>
    </div>
</template>
