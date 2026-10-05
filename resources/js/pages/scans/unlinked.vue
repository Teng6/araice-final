<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { store } from '@/actions/App/Http/Controllers/ScanReviewController';

interface Disease {
    id: number;
    name: string;
}

interface Scan {
    id: number;
    image_url: string;
    gps_lat: number | null;
    gps_long: number | null;
    scan_date: string;
    disease: Disease;
}

interface Outbreak {
    id: number;
    municipality: string;
    disease_id: number;
    disease: string;
}

const props = defineProps<{
    scans: {
        data: Scan[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    outbreaks: Outbreak[];
}>();

const selectedOutbreaks = reactive<Record<number, number | null>>({});
const form = useForm({ outbreak_id: null as number | null });
const failedScanId = ref<number | null>(null);

function matchingOutbreaks(scan: Scan): Outbreak[] {
    return props.outbreaks.filter(
        (outbreak) => outbreak.disease_id === scan.disease.id,
    );
}

function linkOutbreak(scanId: number): void {
    failedScanId.value = scanId;
    form.clearErrors();
    form.outbreak_id = selectedOutbreaks[scanId] ?? null;
    form.post(store(scanId).url, {
        preserveScroll: true,
        onSuccess: () => {
            selectedOutbreaks[scanId] = null;
            failedScanId.value = null;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Unlinked Scans" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Unlinked scans
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <p v-if="scans.data.length === 0" class="text-gray-600">
                    No unlinked scans to review.
                </p>

                <div
                    v-for="scan in scans.data"
                    :key="scan.id"
                    class="rounded-lg bg-white p-6 shadow-sm"
                >
                    <div class="flex flex-col gap-6 sm:flex-row">
                        <img
                            :src="`/storage/${scan.image_url}`"
                            :alt="`Scan ${scan.id}`"
                            class="h-40 w-40 rounded object-cover"
                        />

                        <div class="flex-1 space-y-2">
                            <h3 class="text-lg font-semibold">
                                Scan #{{ scan.id }} — {{ scan.disease.name }}
                            </h3>
                            <p>
                                GPS:
                                {{
                                    scan.gps_lat !== null &&
                                    scan.gps_long !== null
                                        ? `${scan.gps_lat}, ${scan.gps_long}`
                                        : 'Unavailable'
                                }}
                            </p>
                            <p>Date: {{ scan.scan_date }}</p>

                            <form
                                class="flex flex-col gap-3 sm:flex-row sm:items-start"
                                @submit.prevent="linkOutbreak(scan.id)"
                            >
                                <div class="flex-1">
                                    <label
                                        :for="`outbreak-${scan.id}`"
                                        class="sr-only"
                                    >
                                        Active outbreak for
                                        {{ scan.disease.name }}
                                    </label>
                                    <select
                                        :id="`outbreak-${scan.id}`"
                                        v-model="selectedOutbreaks[scan.id]"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option :value="null" disabled>
                                            Select an active outbreak
                                        </option>
                                        <option
                                            v-for="outbreak in matchingOutbreaks(
                                                scan,
                                            )"
                                            :key="outbreak.id"
                                            :value="outbreak.id"
                                        >
                                            {{ outbreak.disease }} —
                                            {{ outbreak.municipality }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            failedScanId === scan.id &&
                                            form.errors.outbreak_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.outbreak_id }}
                                    </p>
                                    <p
                                        v-if="
                                            matchingOutbreaks(scan).length === 0
                                        "
                                        class="mt-1 text-sm text-gray-600"
                                    >
                                        No active outbreak matches this disease.
                                    </p>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="
                                        form.processing ||
                                        selectedOutbreaks[scan.id] == null
                                    "
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    Confirm
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <nav class="flex flex-wrap gap-2">
                    <template v-for="(link, i) in scans.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="rounded border px-3 py-1"
                            :class="{ 'bg-gray-200': link.active }"
                        />
                        <span v-else v-html="link.label" class="px-3 py-1" />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
