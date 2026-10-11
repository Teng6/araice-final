<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { index as alertsIndex } from '@/actions/App/Http/Controllers/AlertController';
import {
    create as scansCreate,
    show as scanShow,
} from '@/actions/App/Http/Controllers/ScanController';
import type { ActiveOutbreak, RecentScan } from '@/types';

const props = defineProps<{
    scans_count?: number;
    recent_scans?: RecentScan[];
    active_outbreak?: ActiveOutbreak | null;
}>();

const page = usePage();

const statusClasses: Record<RecentScan['status'], string> = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    processing: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    failed: 'bg-red-50 text-red-700 ring-red-600/20',
};

const severityClasses: Record<ActiveOutbreak['severity'], string> = {
    low: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    medium: 'bg-orange-100 text-orange-800 ring-orange-600/20',
    high: 'bg-red-100 text-red-800 ring-red-600/20',
};

function confidencePercentage(score: string | null): string {
    return score === null ? '—' : `${(Number(score) * 100).toFixed(1)}%`;
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Dashboard
            </h2>
        </template>

        <main
            v-if="props.scans_count !== undefined"
            class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8"
        >
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Workspace</p>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Farmer dashboard
                    </h2>
                </div>
                <Link
                    :href="scansCreate().url"
                    class="inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700"
                >
                    New scan
                </Link>
            </div>

            <section
                class="grid gap-4 sm:grid-cols-2"
                aria-label="Dashboard summary"
            >
                <article
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <p class="text-sm font-medium text-gray-500">Total scans</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">
                        {{ props.scans_count }}
                    </p>
                </article>

                <Link
                    :href="alertsIndex().url"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-emerald-300 hover:shadow-md"
                >
                    <p class="text-sm font-medium text-gray-500">
                        Unread alerts
                    </p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-700">
                        {{ page.props.unread_alerts_count }}
                    </p>
                    <span class="mt-2 block text-sm text-emerald-700">
                        View alerts
                    </span>
                </Link>
            </section>

            <section
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                aria-label="Municipality outbreak status"
            >
                <h3 class="font-semibold text-gray-900">
                    Municipality outbreak status
                </h3>
                <p
                    v-if="!props.active_outbreak"
                    class="mt-3 text-sm text-gray-600"
                >
                    No active outbreaks in your municipality
                </p>
                <div v-else class="mt-3 flex flex-wrap items-center gap-3">
                    <p class="font-medium text-gray-900">
                        {{ props.active_outbreak.disease }}
                    </p>
                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                        :class="severityClasses[props.active_outbreak.severity]"
                    >
                        {{ props.active_outbreak.severity }} severity
                    </span>
                    <span class="text-sm text-gray-500">
                        Started {{ props.active_outbreak.started_at ?? '—' }}
                    </span>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                aria-label="Recent scans"
            >
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Recent scans</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Your latest leaf analyses
                    </p>
                </div>

                <ul
                    v-if="props.recent_scans?.length"
                    class="divide-y divide-gray-100"
                >
                    <li v-for="scan in props.recent_scans" :key="scan.id">
                        <Link
                            :href="scanShow(scan.id).url"
                            class="flex flex-col gap-3 px-6 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ scan.disease ?? 'No disease detected' }}
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Confidence:
                                    {{
                                        confidencePercentage(
                                            scan.confidence_score,
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                class="flex flex-wrap items-center gap-3 text-sm"
                            >
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                                    :class="statusClasses[scan.status]"
                                >
                                    {{ scan.status }}
                                </span>
                                <time class="text-gray-500">
                                    {{ scan.scan_date }}
                                </time>
                            </div>
                        </Link>
                    </li>
                </ul>

                <div v-else class="px-6 py-10 text-center">
                    <p class="text-sm text-gray-600">
                        No scans yet. Start a scan to see your results here.
                    </p>
                    <Link
                        :href="scansCreate().url"
                        class="mt-4 inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800"
                    >
                        New scan
                    </Link>
                </div>
            </section>
        </main>

        <div v-else class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">You're logged in!</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
