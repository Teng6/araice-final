<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { edit as editFarmer } from '@/actions/App/Http/Controllers/Admin/FarmerController';
import { show as showScan } from '@/actions/App/Http/Controllers/ScanController';
import { formatDate } from '@/lib/formatDate';

interface FarmerDetails {
    id: number;
    full_name: string;
    email: string;
    barangay: string;
    municipality: string;
    contact_number: string;
    plot_count: number | null;
    farm_lat: number | string;
    farm_long: number | string;
    registered_at: string;
    is_deactivated: boolean;
    scan_count: number;
    latest_scans: { id: number; disease: string | null; scan_date: string }[];
}

defineProps<{
    farmer: FarmerDetails;
    flash: { success: string | null };
}>();
</script>

<template>
    <Head :title="farmer.full_name" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <p
                v-if="flash.success"
                role="status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800"
            >
                {{ flash.success }}
            </p>

            <section class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-[#14391f]">
                            {{ farmer.full_name }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600">Farmer profile</p>
                    </div>
                    <Link
                        :href="editFarmer(farmer.id).url"
                        class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2 text-sm font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19]"
                    >
                        Edit farmer
                    </Link>
                </div>

                <dl class="mt-8 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-gray-500">Email</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            {{ farmer.email }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Barangay</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            {{ farmer.barangay }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Municipality</dt>
                        <dd class="mt-1 font-medium text-gray-900 capitalize">
                            {{ farmer.municipality }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Contact number</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            {{ farmer.contact_number }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Number of plots</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            {{ farmer.plot_count ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Farm coordinates</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            {{ farmer.farm_lat }}, {{ farmer.farm_long }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Registered</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            {{ formatDate(farmer.registered_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Account status</dt>
                        <dd class="mt-1">
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                :class="
                                    farmer.is_deactivated
                                        ? 'bg-rose-50 text-rose-800 ring-rose-600/20'
                                        : 'bg-emerald-50 text-emerald-800 ring-emerald-600/20'
                                "
                            >
                                {{
                                    farmer.is_deactivated
                                        ? 'Deactivated'
                                        : 'Active'
                                }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-5">
                    <div>
                        <h2 class="text-lg font-semibold text-[#14391f]">
                            Scans
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ farmer.scan_count }} total
                        </p>
                    </div>
                </div>
                <p
                    v-if="farmer.latest_scans.length === 0"
                    class="px-6 pb-6 text-sm text-gray-500"
                >
                    No scans recorded.
                </p>
                <ul v-else class="divide-y divide-gray-100">
                    <li
                        v-for="scan in farmer.latest_scans"
                        :key="scan.id"
                        class="flex flex-wrap items-center justify-between gap-3 px-6 py-4"
                    >
                        <Link
                            :href="showScan(scan.id).url"
                            class="font-medium text-[#14391f] underline-offset-4 hover:underline"
                        >
                            {{ scan.disease ?? 'Unknown disease' }}
                        </Link>
                        <time class="text-sm text-gray-600">
                            {{ formatDate(scan.scan_date) }}
                        </time>
                    </li>
                </ul>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
