<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/ScanController';
import type { Scan } from '@/types';

defineProps<{ scan: Scan }>();

const statusClasses: Record<Scan['status'], string> = {
    pending: 'bg-gray-100 text-gray-700',
    processing: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
};

function confidenceClasses(score: number): string {
    if (score >= 0.85) return 'bg-green-100 text-green-700';
    if (score >= 0.6) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-700';
}

function treatmentClasses(type: string): string {
    return type.toLowerCase().includes('chem')
        ? 'bg-orange-100 text-orange-700'
        : 'bg-emerald-100 text-emerald-700';
}
</script>

<template>
    <Head title="Scan Result" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Scan #{{ scan.id }}
                </h2>
                <Link
                    :href="index().url"
                    class="text-sm text-indigo-600 hover:underline"
                >
                    &larr; Back to scans
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 py-6 sm:px-6 lg:px-8">
            <!-- Top: image + summary -->
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <img
                        :src="`/storage/${scan.image_url}`"
                        alt="Scan image"
                        class="h-80 w-full object-cover"
                    />
                </div>

                <div class="rounded-lg bg-white p-6 shadow lg:col-span-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 capitalize"
                        >
                            {{ scan.scan_type }} scan
                        </span>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-medium capitalize"
                            :class="statusClasses[scan.status]"
                        >
                            {{ scan.status }}
                        </span>
                        <span
                            v-if="
                                scan.status === 'completed' &&
                                scan.confidence_score !== null
                            "
                            class="rounded-full px-3 py-1 text-xs font-medium"
                            :class="confidenceClasses(scan.confidence_score)"
                        >
                            {{ (scan.confidence_score * 100).toFixed(1) }}%
                            confidence
                        </span>
                    </div>

                    <!-- Result headline -->
                    <div class="mt-5">
                        <template v-if="scan.status === 'completed'">
                            <p
                                class="text-xs tracking-wide text-gray-500 uppercase"
                            >
                                {{
                                    scan.scan_type === 'leaf'
                                        ? 'Detected disease'
                                        : 'Detected variety'
                                }}
                            </p>
                            <p
                                class="mt-1 text-3xl font-semibold text-gray-900"
                            >
                                {{
                                    scan.scan_type === 'leaf'
                                        ? (scan.disease?.name ??
                                          'No match found')
                                        : (scan.variety?.name ?? '—')
                                }}
                            </p>
                            <p
                                v-if="scan.scan_type === 'leaf' && scan.variety"
                                class="mt-1 text-sm text-gray-600"
                            >
                                Variety: {{ scan.variety.name }}
                            </p>
                        </template>

                        <div
                            v-else-if="scan.status === 'failed'"
                            class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                        >
                            Analysis failed. Please try again.
                        </div>

                        <div
                            v-else
                            class="rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700"
                        >
                            This scan is still being analyzed.
                        </div>
                    </div>

                    <!-- Details row -->
                    <dl
                        class="mt-6 grid gap-4 border-t border-gray-100 pt-4 text-sm sm:grid-cols-3"
                    >
                        <div>
                            <dt class="text-gray-500">Date</dt>
                            <dd class="mt-0.5 font-medium text-gray-900">
                                {{ scan.scan_date }}
                            </dd>
                        </div>
                        <div v-if="scan.gps_lat !== null">
                            <dt class="text-gray-500">Location</dt>
                            <dd class="mt-0.5 font-medium text-gray-900">
                                {{ scan.gps_lat }}, {{ scan.gps_long }}
                            </dd>
                        </div>
                        <div v-if="scan.farmer">
                            <dt class="text-gray-500">Farmer</dt>
                            <dd class="mt-0.5 font-medium text-gray-900">
                                {{ scan.farmer.full_name }}
                                <span
                                    v-if="scan.farmer.contact_number"
                                    class="block text-xs font-normal text-gray-500"
                                >
                                    {{ scan.farmer.contact_number }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Leaf, completed, no match -->
            <div
                v-if="
                    scan.status === 'completed' &&
                    scan.scan_type === 'leaf' &&
                    !scan.disease
                "
                class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
            >
                No disease matched this leaf. Try a clearer, closer photo in
                good lighting.
            </div>

            <!-- Disease info -->
            <template
                v-if="
                    scan.status === 'completed' &&
                    scan.scan_type === 'leaf' &&
                    scan.disease
                "
            >
                <div class="grid gap-6 md:grid-cols-2">
                    <section
                        v-if="scan.disease.description"
                        class="rounded-lg bg-white p-6 shadow md:col-span-2"
                    >
                        <h3 class="text-base font-semibold text-gray-900">
                            Description
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-700">
                            {{ scan.disease.description }}
                        </p>
                    </section>

                    <section
                        v-if="scan.disease.symptoms"
                        class="rounded-lg bg-white p-6 shadow"
                    >
                        <h3 class="text-base font-semibold text-gray-900">
                            Symptoms
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-700">
                            {{ scan.disease.symptoms }}
                        </p>
                    </section>

                    <section
                        v-if="scan.disease.causes"
                        class="rounded-lg bg-white p-6 shadow"
                    >
                        <h3 class="text-base font-semibold text-gray-900">
                            Causes
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-700">
                            {{ scan.disease.causes }}
                        </p>
                    </section>

                    <section
                        v-if="scan.disease.prevention_tips"
                        class="rounded-lg border border-green-200 bg-green-50 p-6 md:col-span-2"
                    >
                        <h3 class="text-base font-semibold text-green-900">
                            Prevention Tips
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed whitespace-pre-line text-green-900"
                        >
                            {{ scan.disease.prevention_tips }}
                        </p>
                    </section>
                </div>

                <section
                    v-if="scan.disease.treatments.length"
                    class="rounded-lg bg-white p-6 shadow"
                >
                    <h3 class="text-base font-semibold text-gray-900">
                        Recommended Treatments
                    </h3>
                    <ul class="mt-4 grid gap-4 md:grid-cols-3">
                        <li
                            v-for="treatment in scan.disease.treatments"
                            :key="treatment.id"
                            class="rounded-md border border-gray-200 p-4"
                        >
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                :class="treatmentClasses(treatment.type)"
                            >
                                {{ treatment.type }}
                            </span>
                            <h4
                                class="mt-2 text-sm font-semibold text-gray-900"
                            >
                                {{ treatment.title }}
                            </h4>
                            <p class="mt-1 text-sm text-gray-700">
                                {{ treatment.description }}
                            </p>
                        </li>
                    </ul>
                </section>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
