<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { store } from '@/actions/App/Http/Controllers/Admin/UserController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'lgu_staff' as 'lgu_staff' | 'admin',
});

function submit(): void {
    form.submit(store());
}
</script>

<template>
    <Head title="Add account" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <form
                class="mx-auto max-w-4xl space-y-6 rounded-2xl bg-white p-6 shadow-sm sm:p-8"
                @submit.prevent="submit"
            >
                <div>
                    <h1 class="text-lg font-semibold text-[#14391f]">
                        Add account
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Create an administrator or LGU staff account.
                    </p>
                </div>

                <div>
                    <label
                        for="name"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Name <span aria-hidden="true">(required)</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autocomplete="name"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label
                        for="email"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Email <span aria-hidden="true">(required)</span>
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    />
                    <p
                        v-if="form.errors.email"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label
                        for="role"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Role <span aria-hidden="true">(required)</span>
                    </label>
                    <select
                        id="role"
                        v-model="form.role"
                        required
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    >
                        <option value="lgu_staff">LGU Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                    <p
                        v-if="form.errors.role"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.role }}
                    </p>
                </div>

                <div>
                    <label
                        for="password"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Password <span aria-hidden="true">(required)</span>
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    />
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label
                        for="password_confirmation"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Confirm password
                        <span aria-hidden="true">(required)</span>
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    />
                    <p
                        v-if="form.errors.password_confirmation"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <Link
                        :href="usersIndex().url"
                        class="inline-flex items-center rounded-full border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 transition hover:bg-[#FBF8EA]"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2 text-sm font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Create account
                    </button>
                </div>
            </form>
        </main>
    </AuthenticatedLayout>
</template>
