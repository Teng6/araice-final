<script setup lang="ts">
import { computed } from 'vue';
import EncyclopediaLayout from '@/Layouts/EncyclopediaLayout.vue';
import {
    destroy as destroyDisease,
    edit as editDisease,
} from '@/actions/App/Http/Controllers/Admin/DiseaseController';
import { index } from '@/actions/App/Http/Controllers/EncyclopediaController';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import type { Disease } from '@/types';

const props = defineProps<{
    disease: Disease;
}>();

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const deleteForm = useForm<Record<string, string>>({});

function deleteDisease() {
    if (
        !window.confirm(
            'Deleting this disease will also remove all of its treatments. Are you sure you want to continue?',
        )
    ) {
        return;
    }

    deleteForm.submit(destroyDisease({ disease: props.disease.id }));
}
</script>

<template>
    <Head :title="disease.name" />

    <EncyclopediaLayout>
        <div class="mx-auto max-w-3xl px-4 py-8">
            <Link :href="index()" class="text-sm text-gray-600 hover:underline"
                >← Back to encyclopedia</Link
            >

            <h1 class="mt-4 text-2xl font-semibold">{{ disease.name }}</h1>
            <p class="mt-2 text-gray-700">{{ disease.description }}</p>

            <div v-if="isAdmin" class="mt-4 space-y-3">
                <div class="flex items-center gap-3">
                    <Link
                        :href="editDisease({ disease: disease.id }).url"
                        class="inline-flex items-center rounded-md bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700"
                    >
                        Edit
                    </Link>
                    <button
                        type="button"
                        :disabled="deleteForm.processing"
                        class="inline-flex items-center rounded-md bg-red-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="deleteDisease"
                    >
                        Delete
                    </button>
                </div>
                <p
                    v-if="deleteForm.errors.delete"
                    role="alert"
                    class="text-sm text-red-600"
                >
                    {{ deleteForm.errors.delete }}
                </p>
            </div>

            <section v-if="disease.causes" class="mt-6">
                <h2 class="font-medium">Causes</h2>
                <p class="mt-1 whitespace-pre-line text-gray-700">
                    {{ disease.causes }}
                </p>
            </section>

            <section v-if="disease.symptoms" class="mt-6">
                <h2 class="font-medium">Symptoms</h2>
                <p class="mt-1 whitespace-pre-line text-gray-700">
                    {{ disease.symptoms }}
                </p>
            </section>

            <section v-if="disease.history" class="mt-6">
                <h2 class="font-medium">History</h2>
                <p class="mt-1 whitespace-pre-line text-gray-700">
                    {{ disease.history }}
                </p>
            </section>

            <section v-if="disease.prevention_tips" class="mt-6">
                <h2 class="font-medium">Prevention</h2>
                <p class="mt-1 whitespace-pre-line text-gray-700">
                    {{ disease.prevention_tips }}
                </p>
            </section>

            <section v-if="disease.treatments.length" class="mt-6">
                <h2 class="font-medium">Treatments</h2>
                <div class="mt-2 space-y-3">
                    <div
                        v-for="treatment in disease.treatments"
                        :key="treatment.id"
                        class="rounded-lg border bg-white p-4"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-medium">{{ treatment.title }}</h3>
                            <span
                                class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700"
                                >{{ treatment.type }}</span
                            >
                        </div>
                        <p
                            class="mt-2 text-sm whitespace-pre-line text-gray-700"
                        >
                            {{ treatment.description }}
                        </p>
                    </div>
                </div>
            </section>

            <section v-if="disease.sources" class="mt-8 border-t pt-4">
                <h2 class="text-sm font-medium">Sources</h2>
                <p class="mt-1 text-xs whitespace-pre-line text-gray-500">
                    {{ disease.sources }}
                </p>
            </section>
        </div>
    </EncyclopediaLayout>
</template>
