<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/AlertController';
import type { Alert } from '@/types';

defineProps<{ alert: Alert }>();
</script>

<template>
    <Head title="Alert" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    {{ alert.disease }} outbreak · {{ alert.municipality }}
                </h2>
                <Link
                    :href="index().url"
                    class="text-sm text-indigo-600 hover:underline"
                >
                    Back to alerts
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-4xl py-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <p class="text-sm text-gray-600 capitalize">
                    Severity: {{ alert.severity }}
                </p>
                <p class="mt-4 text-base text-gray-800">{{ alert.message }}</p>
                <p class="mt-4 text-sm text-gray-500">
                    Issued {{ new Date(alert.created_at).toLocaleString() }}
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
