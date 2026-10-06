<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    create,
    index as scansIndex,
    show,
} from '@/actions/App/Http/Controllers/ScanController';

interface Scan {
    id: number;
    scan_type: 'leaf' | 'grain';
    status: 'pending' | 'processing' | 'completed' | 'failed';
    confidence_score: number | null;
    scan_date: string;
    disease: { id: number; name: string } | null;
    variety: { id: number; name: string } | null;
    farmer?: {
        id: number;
        full_name: string;
        contact_number: string | null;
    } | null;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    scans: { data: Scan[]; links: PageLink[] };
}>();

const statusClasses: Record<Scan['status'], string> = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    processing: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    failed: 'bg-red-50 text-red-700 ring-red-600/20',
};
</script>

<template>
    <Head title="Scans" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Workspace</p>
                    <h2
                        class="text-xl leading-tight font-semibold text-gray-800"
                    >
                        Scan history
                    </h2>
                </div>
                <Link
                    :href="create().url"
                    class="inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700"
                >
                    New scan
                </Link>
            </div>
        </template>

        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm">
                <Link
                    :href="scansIndex().url"
                    class="font-medium text-emerald-700 hover:text-emerald-800"
                >
                    Scans
                </Link>
                <span class="px-2 text-gray-400">/</span>
                <span class="text-gray-500">History</span>
            </nav>

            <section
                v-if="scans.data.length === 0"
                class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-700"
                    aria-hidden="true"
                >
                    <span class="text-xl font-semibold">+</span>
                </div>
                <h3 class="mt-4 text-base font-semibold text-gray-900">
                    No scans yet
                </h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                    Upload a leaf or grain photo to get your first analysis
                    result.
                </p>
                <Link
                    :href="create().url"
                    class="mt-6 inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800"
                >
                    Start a scan
                </Link>
            </section>

            <section
                v-else
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                aria-label="Scan history"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-200 px-6 py-4"
                >
                    <div>
                        <h3 class="font-semibold text-gray-900">All scans</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Recent analyses and their results
                        </p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-gray-200 text-left text-sm"
                    >
                        <thead
                            class="bg-gray-50 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            <tr>
                                <th scope="col" class="px-6 py-3">Scan</th>
                                <th
                                    v-if="scans.data[0]?.farmer !== undefined"
                                    scope="col"
                                    class="px-6 py-3"
                                >
                                    Farmer
                                </th>
                                <th scope="col" class="px-6 py-3">Type</th>
                                <th scope="col" class="px-6 py-3">Result</th>
                                <th scope="col" class="px-6 py-3">
                                    Confidence
                                </th>
                                <th scope="col" class="px-6 py-3">Status</th>
                                <th scope="col" class="px-6 py-3">Date</th>
                                <th scope="col" class="px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="scan in scans.data"
                                :key="scan.id"
                                class="transition hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-4 font-medium whitespace-nowrap"
                                >
                                    <Link
                                        :href="show(scan.id).url"
                                        class="text-emerald-700 hover:text-emerald-800 hover:underline"
                                    >
                                        #{{ scan.id }}
                                    </Link>
                                </td>
                                <td
                                    v-if="scan.farmer !== undefined"
                                    class="px-6 py-4"
                                >
                                    <span class="font-medium text-gray-900">
                                        {{ scan.farmer?.full_name ?? '—' }}
                                    </span>
                                    <span
                                        v-if="scan.farmer?.contact_number"
                                        class="mt-1 block text-xs text-gray-500"
                                    >
                                        {{ scan.farmer.contact_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 capitalize"
                                    >
                                        {{ scan.scan_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{
                                        scan.scan_type === 'leaf'
                                            ? (scan.disease?.name ?? '—')
                                            : (scan.variety?.name ?? '—')
                                    }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{
                                        scan.confidence_score !== null
                                            ? (
                                                  scan.confidence_score * 100
                                              ).toFixed(1) + '%'
                                            : '—'
                                    }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                                        :class="statusClasses[scan.status]"
                                    >
                                        {{ scan.status }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-gray-600"
                                >
                                    {{ scan.scan_date }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="show(scan.id).url"
                                        class="font-medium text-emerald-700 hover:text-emerald-800 hover:underline"
                                    >
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <nav
                v-if="scans.links.length > 3"
                aria-label="Pagination"
                class="flex flex-wrap items-center justify-end gap-1"
            >
                <template v-for="(link, i) in scans.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md border px-3 py-2 text-sm transition"
                        :class="
                            link.active
                                ? 'border-emerald-700 bg-emerald-700 text-white'
                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-400"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </main>
    </AuthenticatedLayout>
</template>
