<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { index as encyclopediaIndex } from '@/actions/App/Http/Controllers/EncyclopediaController';
import { register } from '@/routes';
</script>

<template>
    <Head title="A-RAICE" />

    <PublicLayout>
        <section
            class="mx-auto flex min-h-[60vh] max-w-5xl flex-col items-center justify-center rounded-2xl bg-green-50 px-6 py-20 text-center"
        >
            <h1
                class="text-4xl font-bold tracking-tight text-green-900 sm:text-5xl"
            >
                A-RAICE
            </h1>
            <p class="mt-5 max-w-2xl text-lg text-gray-700">
                AI-assisted rice disease detection and outbreak monitoring for
                Bataan farmers and LGUs
            </p>
            <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row">
                <Link
                    :href="register().url"
                    class="rounded-md bg-green-700 px-6 py-3 font-medium text-white transition hover:bg-green-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-700"
                >
                    Get started
                </Link>
                <Link
                    :href="encyclopediaIndex().url"
                    class="rounded-md border border-green-700 px-6 py-3 font-medium text-green-700 transition hover:bg-green-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-700"
                >
                    Browse the encyclopedia
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
