<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/EncyclopediaController';
import type { Disease } from '@/types';

defineProps<{
    disease: Disease;
}>();
</script>

<template>
    <Head :title="disease.name" />

    <PublicLayout>
        <div class="mx-auto max-w-3xl px-4 py-8">
            <Link :href="index()" class="text-sm text-gray-600 hover:underline"
                >← Back to encyclopedia</Link
            >

            <h1 class="mt-4 text-2xl font-semibold">{{ disease.name }}</h1>
            <p class="mt-2 text-gray-700">{{ disease.description }}</p>

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
    </PublicLayout>
</template>
