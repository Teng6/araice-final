<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { show } from '@/actions/App/Http/Controllers/AlertController';
import type { Alert } from '@/types';

defineProps<{
    alerts: {
        data: Alert[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const severityClass = {
    low: 'bg-yellow-100 text-yellow-800',
    medium: 'bg-orange-100 text-orange-800',
    high: 'bg-red-100 text-red-800',
} as const;
</script>
app

<template>
    <Head title="Alerts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Alerts
                </h2>
            </div>
        </template>

        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
            <p v-if="alerts.data.length === 0" class="text-gray-500">
                No alerts yet.
            </p>

            <ul
                v-else
                class="divide-y rounded-lg border border-gray-200 bg-white"
            >
                <li v-for="alert in alerts.data" :key="alert.id">
                    <Link
                        :href="show(alert.id).url"
                        class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50"
                        :class="
                            alert.read_at === null
                                ? 'bg-blue-50 font-semibold'
                                : ''
                        "
                    >
                        <div>
                            <div>
                                {{ alert.disease }} · {{ alert.municipality }}
                            </div>
                            <div class="text-sm font-normal text-gray-600">
                                {{ alert.message }}
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <span
                                class="rounded px-2 py-0.5 capitalize"
                                :class="severityClass[alert.severity]"
                            >
                                {{ alert.severity }}
                            </span>
                            <span class="text-gray-500">
                                {{
                                    new Date(
                                        alert.created_at,
                                    ).toLocaleDateString()
                                }}
                            </span>
                        </div>
                    </Link>
                </li>
            </ul>

            <nav v-if="alerts.links.length > 3" class="mt-4 flex gap-1">
                <template v-for="link in alerts.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border px-3 py-1 text-sm"
                        :class="link.active ? 'bg-gray-800 text-white' : ''"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="px-3 py-1 text-sm text-gray-400"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AuthenticatedLayout>
</template>
