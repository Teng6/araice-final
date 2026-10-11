<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { close as closeOutbreak } from '@/actions/App/Http/Controllers/OutbreakController';

interface OutbreakRow {
    id: number;
    disease: string;
    municipality: string;
    status: 'active' | 'resolved';
    severity: 'low' | 'medium' | 'high' | null;
    scans_count: number;
    created_at: string;
    closed_at: string | null;
    closed_by: string | null;
}

defineProps<{
    outbreaks: OutbreakRow[];
}>();

const severityClass = {
    low: 'bg-yellow-100 text-yellow-800',
    medium: 'bg-orange-100 text-orange-800',
    high: 'bg-red-100 text-red-800',
};

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '-';
}

function close(id: number): void {
    if (!window.confirm('Close this outbreak? This cannot be undone.')) {
        return;
    }

    router.patch(closeOutbreak(id).url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Outbreaks" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Outbreaks
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-x-auto bg-white p-6 shadow-sm sm:rounded-lg"
                >
                    <p
                        v-if="outbreaks.length === 0"
                        class="text-sm text-gray-500"
                    >
                        No outbreaks yet.
                    </p>

                    <table v-else class="min-w-full text-left text-sm">
                        <thead class="border-b text-gray-500">
                            <tr>
                                <th class="py-2 pe-4">Disease</th>
                                <th class="py-2 pe-4">Municipality</th>
                                <th class="py-2 pe-4">Severity</th>
                                <th class="py-2 pe-4">Scans</th>
                                <th class="py-2 pe-4">Opened</th>
                                <th class="py-2 pe-4">Status</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="o in outbreaks"
                                :key="o.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pe-4 font-medium">
                                    {{ o.disease }}
                                </td>
                                <td class="py-3 pe-4 capitalize">
                                    {{ o.municipality }}
                                </td>
                                <td class="py-3 pe-4">
                                    <span
                                        v-if="o.severity"
                                        class="rounded px-2 py-1 text-xs font-medium capitalize"
                                        :class="severityClass[o.severity]"
                                    >
                                        {{ o.severity }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td class="py-3 pe-4">{{ o.scans_count }}</td>
                                <td class="py-3 pe-4">
                                    {{ formatDate(o.created_at) }}
                                </td>
                                <td class="py-3 pe-4">
                                    <span
                                        v-if="o.status === 'active'"
                                        class="text-red-600"
                                    >
                                        Active
                                    </span>
                                    <span v-else class="text-gray-500">
                                        Resolved
                                        {{ formatDate(o.closed_at) }}
                                        <template v-if="o.closed_by">
                                            by {{ o.closed_by }}
                                        </template>
                                    </span>
                                </td>
                                <td class="py-3 text-right">
                                    <button
                                        v-if="o.status === 'active'"
                                        type="button"
                                        class="rounded bg-gray-800 px-3 py-1 text-xs font-semibold text-white hover:bg-gray-700"
                                        @click="close(o.id)"
                                    >
                                        Close
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
