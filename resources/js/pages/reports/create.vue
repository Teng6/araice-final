<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/ReportController';

defineProps<{
    municipalities: string[];
    diseases: { id: number; name: string }[];
}>();

const form = useForm({
    municipality: '',
    range_start: '',
    range_end: '',
    disease_id: '',
});

function submit() {
    form.post(store().url);
}
</script>

<template>
    <Head title="New Report" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                New Report
            </h2>
        </template>

        <div class="mx-auto max-w-xl py-6 sm:px-6 lg:px-8">
            <div class="space-y-4 rounded-lg bg-white p-6 shadow">
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Municipality</label
                    >
                    <select
                        v-model="form.municipality"
                        class="mt-1 w-full rounded-md border-gray-300"
                    >
                        <option value="" disabled>Select municipality</option>
                        <option v-for="m in municipalities" :key="m" :value="m">
                            {{ m }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.municipality"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.municipality }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >From</label
                        >
                        <input
                            v-model="form.range_start"
                            type="date"
                            class="mt-1 w-full rounded-md border-gray-300"
                        />
                        <p
                            v-if="form.errors.range_start"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.range_start }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >To</label
                        >
                        <input
                            v-model="form.range_end"
                            type="date"
                            class="mt-1 w-full rounded-md border-gray-300"
                        />
                        <p
                            v-if="form.errors.range_end"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.range_end }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Disease (optional)</label
                    >
                    <select
                        v-model="form.disease_id"
                        class="mt-1 w-full rounded-md border-gray-300"
                    >
                        <option value="">All diseases</option>
                        <option v-for="d in diseases" :key="d.id" :value="d.id">
                            {{ d.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.disease_id"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.disease_id }}
                    </p>
                </div>

                <button
                    type="button"
                    :disabled="form.processing"
                    class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                    @click="submit"
                >
                    Generate Report
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
