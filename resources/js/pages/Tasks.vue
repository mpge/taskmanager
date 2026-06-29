<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Calendar,
    Flag,
    GripVertical,
    MoveRight,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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

const props = defineProps<{
    today: string;
    important: TaskItem[];
    eventual: TaskItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tasks', href: '/tasks' }],
    },
});

type LaneKey = 'important' | 'eventual';

const laneMeta: {
    key: LaneKey;
    title: string;
    subtitle: string;
    accent: string;
}[] = [
    {
        key: 'important',
        title: 'Important',
        subtitle: 'Do these now',
        accent: 'var(--primary)',
    },
    {
        key: 'eventual',
        title: 'Eventual',
        subtitle: 'Later, not forgotten',
        accent: 'hsl(150 12% 52%)',
    },
];

const lanes = reactive<Record<LaneKey, TaskItem[]>>({
    important: [...props.important],
    eventual: [...props.eventual],
});

watch(
    () => props.important,
    (v) => (lanes.important = [...v]),
);
watch(
    () => props.eventual,
    (v) => (lanes.eventual = [...v]),
);

const draft = reactive<Record<LaneKey, string>>({
    important: '',
    eventual: '',
});

function addTask(bucket: LaneKey) {
    const title = draft[bucket].trim();

    if (!title) {
        return;
    }

    router.post(
        '/tasks',
        { title, bucket },
        {
            preserveScroll: true,
            onSuccess: () => {
                draft[bucket] = '';
            },
        },
    );
}

function completeTask(task: TaskItem) {
    router.patch(
        `/tasks/${task.id}`,
        { status: 'done' },
        { preserveScroll: true },
    );
}

function deleteTask(task: TaskItem) {
    router.delete(`/tasks/${task.id}`, { preserveScroll: true });
}

function moveLane(task: TaskItem) {
    const to = task.bucket === 'important' ? 'eventual' : 'important';
    router.patch(`/tasks/${task.id}`, { bucket: to }, { preserveScroll: true });
}

// --- drag to reorder within a lane ---
const drag = ref<{ bucket: LaneKey; index: number } | null>(null);

function onDragStart(bucket: LaneKey, index: number) {
    drag.value = { bucket, index };
}

function onDragEnter(bucket: LaneKey, index: number) {
    const current = drag.value;

    if (!current || current.bucket !== bucket || current.index === index) {
        return;
    }

    const list = lanes[bucket];
    const [moved] = list.splice(current.index, 1);
    list.splice(index, 0, moved);
    current.index = index;
}

function onDrop(bucket: LaneKey) {
    if (!drag.value || drag.value.bucket !== bucket) {
        drag.value = null;

        return;
    }

    router.post(
        '/tasks/reorder',
        { ids: lanes[bucket].map((t) => t.id) },
        { preserveScroll: true, preserveState: true },
    );
    drag.value = null;
}

// --- edit dialog ---
const editing = ref<TaskItem | null>(null);
const form = reactive({
    title: '',
    notes: '',
    due_date: '',
    priority: 0,
    bucket: 'important' as LaneKey,
});

function openEdit(task: TaskItem) {
    editing.value = task;
    form.title = task.title;
    form.notes = task.notes ?? '';
    form.due_date = task.due_date ?? '';
    form.priority = task.priority;
    form.bucket = task.bucket as LaneKey;
}

function onEditOpenChange(value: boolean) {
    if (!value) {
        editing.value = null;
    }
}

function saveEdit() {
    if (!editing.value) {
        return;
    }

    router.patch(
        `/tasks/${editing.value.id}`,
        {
            title: form.title,
            notes: form.notes || null,
            due_date: form.due_date || null,
            priority: form.priority,
            bucket: form.bucket,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = null;
            },
        },
    );
}

function isOverdue(date: string | null): boolean {
    if (!date) {
        return false;
    }

    return date < props.today;
}

function formatDue(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('en', {
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Tasks" />

    <div class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
        <header class="mb-6">
            <h1
                class="font-display text-3xl font-semibold tracking-tight text-foreground"
            >
                Tasks
            </h1>
            <p class="mt-1 text-muted-foreground">
                Keep the
                <span class="font-medium text-foreground">Important</span> lane
                short. Everything else can wait in
                <span class="font-medium text-foreground">Eventual</span>.
            </p>
        </header>

        <div class="grid gap-5 md:grid-cols-2">
            <section
                v-for="meta in laneMeta"
                :key="meta.key"
                class="rounded-3xl border border-border/60 bg-card p-4 shadow-sm sm:p-5"
                @dragover.prevent
                @drop="onDrop(meta.key)"
            >
                <div class="mb-3 flex items-center gap-2 px-1">
                    <span
                        class="size-2.5 rounded-full"
                        :style="{ backgroundColor: meta.accent }"
                    />
                    <h2
                        class="font-display text-lg font-semibold text-foreground"
                    >
                        {{ meta.title }}
                    </h2>
                    <span
                        class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                        >{{ lanes[meta.key].length }}</span
                    >
                    <span class="ml-auto text-xs text-muted-foreground">{{
                        meta.subtitle
                    }}</span>
                </div>

                <!-- quick add -->
                <form
                    class="mb-3 flex items-center gap-2"
                    @submit.prevent="addTask(meta.key)"
                >
                    <Input
                        v-model="draft[meta.key]"
                        :placeholder="`Add to ${meta.title}…`"
                        class="h-10 rounded-xl bg-background"
                    />
                    <Button
                        type="submit"
                        size="icon"
                        class="size-10 shrink-0 rounded-xl"
                        :disabled="!draft[meta.key].trim()"
                    >
                        <Plus class="size-5" />
                    </Button>
                </form>

                <ul class="flex flex-col gap-1.5">
                    <li
                        v-for="(task, index) in lanes[meta.key]"
                        :key="task.id"
                        draggable="true"
                        class="group flex items-center gap-2.5 rounded-2xl border border-transparent bg-background/60 px-2.5 py-2.5 transition-colors hover:border-border hover:bg-background"
                        :class="{
                            'opacity-40':
                                drag &&
                                drag.bucket === meta.key &&
                                drag.index === index,
                        }"
                        @dragstart="onDragStart(meta.key, index)"
                        @dragenter="onDragEnter(meta.key, index)"
                        @dragend="drag = null"
                    >
                        <GripVertical
                            class="size-4 shrink-0 cursor-grab text-muted-foreground/40 opacity-0 transition-opacity group-hover:opacity-100"
                        />
                        <button
                            type="button"
                            class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-muted-foreground/40 transition-colors hover:border-success hover:bg-success/10"
                            aria-label="Complete task"
                            @click="completeTask(task)"
                        />
                        <button
                            type="button"
                            class="min-w-0 flex-1 truncate text-left text-foreground"
                            @click="openEdit(task)"
                        >
                            {{ task.title }}
                        </button>

                        <Flag
                            v-if="task.priority >= 2"
                            class="size-4 shrink-0"
                            :style="{
                                color:
                                    task.priority >= 3
                                        ? 'var(--destructive)'
                                        : 'hsl(38 80% 45%)',
                            }"
                        />
                        <span
                            v-if="task.due_date"
                            class="inline-flex shrink-0 items-center gap-1 text-xs font-medium"
                            :class="
                                isOverdue(task.due_date)
                                    ? 'text-destructive'
                                    : 'text-muted-foreground'
                            "
                        >
                            <Calendar class="size-3" />
                            {{ formatDue(task.due_date) }}
                        </span>

                        <div
                            class="flex shrink-0 items-center opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <button
                                type="button"
                                class="flex size-7 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-foreground"
                                :title="
                                    meta.key === 'important'
                                        ? 'Move to Eventual'
                                        : 'Move to Important'
                                "
                                @click="moveLane(task)"
                            >
                                <MoveRight class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="flex size-7 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-foreground"
                                title="Edit"
                                @click="openEdit(task)"
                            >
                                <Pencil class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="flex size-7 items-center justify-center rounded-lg text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                title="Delete"
                                @click="deleteTask(task)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </li>
                </ul>

                <p
                    v-if="!lanes[meta.key].length"
                    class="rounded-2xl border border-dashed border-border px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    Nothing here yet.
                </p>
            </section>
        </div>
    </div>

    <!-- Edit dialog -->
    <Dialog :open="editing !== null" @update:open="onEditOpenChange">
        <DialogContent class="rounded-3xl sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="font-display">Edit task</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="saveEdit">
                <div class="space-y-1.5">
                    <Label for="t-title">Title</Label>
                    <Input id="t-title" v-model="form.title" required />
                </div>
                <div class="space-y-1.5">
                    <Label for="t-notes">Notes</Label>
                    <textarea
                        id="t-notes"
                        v-model="form.notes"
                        rows="3"
                        class="w-full resize-none rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label for="t-due">Due date</Label>
                        <Input id="t-due" v-model="form.due_date" type="date" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="t-priority">Priority</Label>
                        <select
                            id="t-priority"
                            v-model.number="form.priority"
                            class="h-9 w-full rounded-xl border border-input bg-background px-3 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option :value="0">Normal</option>
                            <option :value="1">Medium</option>
                            <option :value="2">High</option>
                            <option :value="3">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <Label for="t-bucket">Lane</Label>
                    <select
                        id="t-bucket"
                        v-model="form.bucket"
                        class="h-9 w-full rounded-xl border border-input bg-background px-3 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option value="important">Important</option>
                        <option value="eventual">Eventual</option>
                    </select>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="editing = null"
                        >Cancel</Button
                    >
                    <Button type="submit">Save changes</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
