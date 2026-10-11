<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    create as scansCreate,
    show as scanShow,
} from '@/actions/App/Http/Controllers/ScanController';
import type { RecentScan, StaffRecentScan } from '@/types';

defineProps<{
    scans: (RecentScan | StaffRecentScan)[];
}>();

const statusClasses: Record<RecentScan['status'], string> = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    processing: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    failed: 'bg-red-50 text-red-700 ring-red-600/20',
};

function confidencePercentage(score: string | null): string {
    return score === null ? '—' : `${(Number(score) * 100).toFixed(1)}%`;
}
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
        aria-label="Recent scans"
    >
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="font-semibold text-gray-900">Recent scans</h3>
            <p class="mt-1 text-sm text-gray-500">Your latest leaf analyses</p>
        </div>

        <ul v-if="scans.length" class="divide-y divide-gray-100">
            <li v-for="scan in scans" :key="scan.id">
                <Link
                    :href="scanShow(scan.id).url"
                    class="flex flex-col gap-3 px-6 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="font-medium text-gray-900">
                            {{ scan.disease ?? 'No disease detected' }}
                        </p>
                        <p
                            v-if="'farmer_name' in scan"
                            class="mt-1 text-sm text-gray-500"
                        >
                            {{ scan.farmer_name ?? 'Farmer unavailable' }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Confidence:
                            {{ confidencePercentage(scan.confidence_score) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm">
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
</template>
