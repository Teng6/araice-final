<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { index as farmersIndex } from '@/actions/App/Http/Controllers/Admin/FarmerController';
import { show as showFarmer } from '@/actions/App/Http/Controllers/Admin/FarmerController';
import { ref, watch } from 'vue';

interface FarmerRow {
    id: number;
    full_name: string;
    email: string;
    barangay: string;
    municipality: string;
    contact_number: string;
    plot_count: number | null;
    scan_count: number;
    is_deactivated: boolean;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    farmers: { data: FarmerRow[]; links: PageLink[] };
    filters: { search: string | null; municipality: string | null };
    municipalities: string[];
    flash: { success: string | null };
}>();

const search = ref(props.filters.search ?? '');
const municipality = ref(props.filters.municipality ?? '');

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        municipality.value = filters.municipality ?? '';
    },
);

function applyFilters(): void {
    const query: { search?: string; municipality?: string } = {};
    const searchValue = search.value.trim();

    if (searchValue) {
        query.search = searchValue;
    }

    if (municipality.value) {
        query.municipality = municipality.value;
    }

    router.get(farmersIndex().url, query, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Farmers" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <p
                v-if="flash.success"
                role="status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800"
            >
                {{ flash.success }}
            </p>

            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <form
                    class="flex flex-col gap-3 sm:flex-row"
                    role="search"
                    @submit.prevent="applyFilters"
                >
                    <label class="sr-only" for="farmer-search">
                        Search by name or email
                    </label>
                    <input
                        id="farmer-search"
                        v-model="search"
                        type="search"
                        placeholder="Search name or email"
                        class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    />
                    <label class="sr-only" for="farmer-municipality">
                        Filter by municipality
                    </label>
                    <select
                        id="farmer-municipality"
                        v-model="municipality"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    >
                        <option value="">All municipalities</option>
                        <option
                            v-for="town in municipalities"
                            :key="town"
                            :value="town"
                        >
                            {{ town }}
                        </option>
                    </select>
                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-full bg-[#14391f] px-5 py-2 text-sm font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f]"
                    >
                        Apply filters
                    </button>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <p
                    v-if="farmers.data.length === 0"
                    class="px-6 py-12 text-center text-sm text-gray-500"
                >
                    No farmers found.
                </p>
                <div v-else class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-gray-100 text-left text-sm"
                    >
                        <thead
                            class="bg-[#FBF8EA] text-xs font-semibold tracking-wide text-gray-600 uppercase"
                        >
                            <tr>
                                <th scope="col" class="px-6 py-3">Full name</th>
                                <th scope="col" class="px-6 py-3">Email</th>
                                <th scope="col" class="px-6 py-3">Barangay</th>
                                <th scope="col" class="px-6 py-3">
                                    Municipality
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Contact number
                                </th>
                                <th scope="col" class="px-6 py-3">Plots</th>
                                <th scope="col" class="px-6 py-3">Scans</th>
                                <th scope="col" class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="farmer in farmers.data"
                                :key="farmer.id"
                                class="transition hover:bg-[#FBF8EA]/50"
                            >
                                <td
                                    class="px-6 py-4 font-semibold whitespace-nowrap text-[#14391f]"
                                >
                                    <Link
                                        :href="showFarmer(farmer.id).url"
                                        class="underline-offset-4 hover:underline"
                                    >
                                        {{ farmer.full_name }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ farmer.email }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ farmer.barangay }}
                                </td>
                                <td class="px-6 py-4 text-gray-700 capitalize">
                                    {{ farmer.municipality }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-gray-700"
                                >
                                    {{ farmer.contact_number }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ farmer.plot_count ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ farmer.scan_count }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="farmer.is_deactivated"
                                        class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-800 ring-1 ring-rose-600/20 ring-inset"
                                    >
                                        Deactivated
                                    </span>
                                    <span v-else class="text-gray-500"
                                        >Active</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <nav
                v-if="farmers.links.length > 3"
                aria-label="Pagination"
                class="flex flex-wrap items-center justify-end gap-1"
            >
                <template v-for="(link, i) in farmers.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md border px-3 py-2 text-sm transition"
                        :class="
                            link.active
                                ? 'border-[#14391f] bg-[#14391f] text-[#FBEB9A]'
                                : 'border-gray-200 bg-white text-gray-700 hover:bg-[#FBF8EA]'
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
