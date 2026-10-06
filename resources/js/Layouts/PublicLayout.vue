<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4"
            >
                <div class="flex items-center gap-6">
                    <Link href="/" class="text-lg font-bold">A-RAICE</Link>
                    <Link
                        href="/encyclopedia"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Encyclopedia
                    </Link>
                </div>

                <div class="flex items-center gap-4">
                    <Link
                        v-if="user"
                        href="/dashboard"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="text-gray-600 hover:text-gray-900"
                            >Login</Link
                        >
                        <Link
                            href="/register"
                            class="text-gray-600 hover:text-gray-900"
                            >Register</Link
                        >
                    </template>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-7xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>
