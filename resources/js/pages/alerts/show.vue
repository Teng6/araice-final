<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/AlertController';

type Severity = 'low' | 'medium' | 'high';

interface AlertDetail {
    id: number;
    message: string;
    severity: Severity;
    disease: string | null;
    municipality: string;
    created_at: string | null;
    read_at: string | null;
}

defineProps<{ alert: AlertDetail }>();

const severityClasses: Record<Severity, string> = {
    low: 'bg-yellow-100 text-yellow-800',
    medium: 'bg-orange-100 text-orange-700',
    high: 'bg-red-100 text-red-700',
};

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}
</script>

<template>
    <Head title="Alert" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Alert #{{ alert.id }}
                </h2>
                <Link
                    :href="index().url"
                    class="text-sm text-indigo-600 hover:underline"
                >
                    &larr; Back to alerts
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-3xl py-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full px-3 py-1 text-xs font-medium capitalize"
                        :class="severityClasses[alert.severity]"
                    >
                        {{ alert.severity }} severity
                    </span>
                    <span
                        class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 capitalize"
                    >
                        {{ alert.municipality }}
                    </span>
                </div>

                <p class="mt-4 text-2xl font-semibold text-gray-900">
                    {{ alert.disease ?? 'Unknown disease' }}
                </p>

                <p class="mt-3 text-sm leading-relaxed text-gray-700">
                    {{ alert.message }}
                </p>

                <dl
                    class="mt-6 grid gap-4 border-t border-gray-100 pt-4 text-sm sm:grid-cols-2"
                >
                    <div>
                        <dt class="text-gray-500">Issued</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">
                            {{ formatDate(alert.created_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Read</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">
                            {{ formatDate(alert.read_at) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
