<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, CreditCard, Leaf, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    billingEnabled: boolean;
    subscribed: boolean;
    onTrial: boolean;
    price: string;
    interval: string;
    trialDays: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Billing', href: '/billing' }],
    },
});

const working = ref(false);

const perks = [
    'Unlimited tasks across Important and Eventual',
    'Habit tracking with streaks and history',
    'Daily focus and gentle nudges',
    'Your data, synced and backed up for you',
];

function subscribe() {
    working.value = true;
    router.post(
        '/billing/checkout',
        {},
        { onFinish: () => (working.value = false) },
    );
}

function manage() {
    window.location.href = '/billing/portal';
}
</script>

<template>
    <Head title="Billing" />

    <div class="mx-auto w-full max-w-2xl px-4 py-8 sm:px-6 sm:py-12">
        <!-- Self-hosted: billing isn't in play -->
        <section
            v-if="!props.billingEnabled"
            class="rounded-3xl border border-border/60 bg-card p-8 text-center"
        >
            <Leaf class="mx-auto size-8" style="color: var(--success)" />
            <h1
                class="mt-4 font-display text-2xl font-semibold text-foreground"
            >
                You're all set
            </h1>
            <p class="mx-auto mt-2 max-w-sm text-muted-foreground">
                This instance is self-hosted, so there's nothing to pay for.
                Everything is already unlocked.
            </p>
        </section>

        <!-- Active subscriber -->
        <section
            v-else-if="props.subscribed"
            class="rounded-3xl border border-border/60 bg-card p-8 text-center"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-card/70 px-3 py-1.5 text-sm font-medium text-foreground ring-1 ring-border/70"
            >
                <Check class="size-4" style="color: var(--success)" />
                {{ props.onTrial ? 'Trial active' : 'Subscription active' }}
            </span>
            <h1
                class="mt-4 font-display text-2xl font-semibold text-foreground"
            >
                Thanks for growing with Tend
            </h1>
            <p class="mx-auto mt-2 max-w-sm text-muted-foreground">
                Your plan is active. You can update your payment method, view
                invoices, or cancel any time from the billing portal.
            </p>
            <Button class="mt-6" @click="manage">
                <CreditCard class="size-4" />
                Manage billing
            </Button>
        </section>

        <!-- Subscribe -->
        <section
            v-else
            class="relative overflow-hidden rounded-3xl border border-border/60 p-8"
            style="
                background-image: linear-gradient(
                    135deg,
                    color-mix(in oklab, var(--primary) 14%, var(--card)),
                    var(--card) 60%
                );
            "
        >
            <Sparkles class="size-7 text-primary" />
            <h1
                class="mt-4 font-display text-3xl font-semibold tracking-tight text-foreground"
            >
                Tend, hosted for you
            </h1>
            <p class="mt-2 max-w-md text-muted-foreground">
                Skip the setup and let us run it. Same calm task and habit
                tracker, kept up to date and backed up.
            </p>

            <div class="mt-6 flex items-baseline gap-1">
                <span
                    class="font-display text-4xl font-semibold text-foreground"
                >
                    {{ props.price }}
                </span>
                <span class="text-muted-foreground"
                    >/ {{ props.interval }}</span
                >
            </div>
            <p
                v-if="props.trialDays > 0"
                class="mt-1 text-sm font-medium text-primary"
            >
                {{ props.trialDays }}-day free trial, cancel anytime
            </p>

            <ul class="mt-6 space-y-2.5">
                <li
                    v-for="perk in perks"
                    :key="perk"
                    class="flex items-start gap-2.5 text-sm text-foreground"
                >
                    <Check class="mt-0.5 size-4 shrink-0 text-primary" />
                    {{ perk }}
                </li>
            </ul>

            <Button
                class="mt-8 w-full sm:w-auto"
                :disabled="working"
                @click="subscribe"
            >
                <CreditCard class="size-4" />
                {{ working ? 'Redirecting…' : 'Subscribe' }}
            </Button>
            <p class="mt-3 text-xs text-muted-foreground">
                Secure checkout via Stripe. Prefer to self-host? Tend is open
                source and free to run yourself.
            </p>
        </section>
    </div>
</template>
