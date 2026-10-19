<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { create as createUser } from '@/actions/App/Http/Controllers/Admin/UserController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
import {
    deactivate as deactivateUser,
    reactivate as reactivateUser,
    resetPassword as resetUserPassword,
    updateRole as updateUserRole,
} from '@/actions/App/Http/Controllers/Admin/UserController';
import { formatDate } from '@/lib/formatDate';
import { ref, watch } from 'vue';

interface UserRow {
    id: number;
    name: string;
    email: string;
    role: 'farmer' | 'admin' | 'lgu_staff';
    is_deactivated: boolean;
    municipality: string | null;
    created_at: string;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    users: { data: UserRow[]; links: PageLink[] };
    filters: { search: string | null; role: UserRow['role'] | null };
    flash: { success: string | null };
    currentUserId: number;
}>();

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');
const processingUserId = ref<number | null>(null);
const passwordDialog = ref<HTMLDialogElement | null>(null);
const passwordUser = ref<UserRow | null>(null);
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        role.value = filters.role ?? '';
    },
);

const roleClasses: Record<UserRow['role'], string> = {
    farmer: 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
    admin: 'bg-[#FBF8EA] text-[#14391f] ring-[#14391f]/20',
    lgu_staff: 'bg-blue-50 text-blue-800 ring-blue-600/20',
};

const roleLabels: Record<UserRow['role'], string> = {
    farmer: 'Farmer',
    admin: 'Admin',
    lgu_staff: 'LGU Staff',
};

function applyFilters(): void {
    const query: { search?: string; role?: string } = {};
    const searchValue = search.value.trim();

    if (searchValue) {
        query.search = searchValue;
    }

    if (role.value) {
        query.role = role.value;
    }

    router.get(usersIndex().url, query, {
        preserveState: true,
        replace: true,
    });
}

function changeRole(user: UserRow, event: Event): void {
    const select = event.target;

    if (!(select instanceof HTMLSelectElement)) {
        return;
    }

    processingUserId.value = user.id;
    router.patch(
        updateUserRole(user.id).url,
        { role: select.value },
        {
            preserveScroll: true,
            onFinish: () => {
                processingUserId.value = null;
            },
        },
    );
}

function setDeactivated(user: UserRow, deactivated: boolean): void {
    const action = deactivated ? 'Deactivate' : 'Reactivate';

    if (!window.confirm(`${action} ${user.name}'s account?`)) {
        return;
    }

    processingUserId.value = user.id;
    router.patch(
        deactivated ? deactivateUser(user.id).url : reactivateUser(user.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingUserId.value = null;
            },
        },
    );
}

function openPasswordDialog(user: UserRow): void {
    passwordUser.value = user;
    passwordDialog.value?.showModal();
}

function resetPasswordDialog(): void {
    passwordForm.reset();
    passwordForm.clearErrors();
    passwordUser.value = null;
}

function handlePasswordDialogCancel(event: Event): void {
    if (passwordForm.processing) {
        event.preventDefault();
    }
}

function submitPasswordReset(): void {
    if (passwordUser.value === null) {
        return;
    }

    passwordForm.patch(resetUserPassword(passwordUser.value.id).url, {
        preserveScroll: true,
        onSuccess: () => passwordDialog.value?.close(),
    });
}
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <p
                v-if="flash.success"
                role="status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800"
            >
                {{ flash.success }}
            </p>

            <div class="flex justify-end">
                <Link
                    :href="createUser().url"
                    class="inline-flex items-center rounded-full bg-[#14391f] px-5 py-2 text-sm font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#14391f]"
                >
                    Add account
                </Link>
            </div>

            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <form
                    class="flex flex-col gap-3 sm:flex-row"
                    role="search"
                    @submit.prevent="applyFilters"
                >
                    <label class="sr-only" for="user-search">
                        Search by name or email
                    </label>
                    <input
                        id="user-search"
                        v-model="search"
                        type="search"
                        placeholder="Search name or email"
                        class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    />
                    <label class="sr-only" for="user-role"
                        >Filter by role</label
                    >
                    <select
                        id="user-role"
                        v-model="role"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-[#14391f] focus:ring-[#14391f]"
                    >
                        <option value="">All roles</option>
                        <option value="admin">Admin</option>
                        <option value="lgu_staff">LGU Staff</option>
                        <option value="farmer">Farmer</option>
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
                    v-if="users.data.length === 0"
                    class="px-6 py-12 text-center text-sm text-gray-500"
                >
                    No users found.
                </p>
                <div v-else class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-gray-100 text-left text-sm"
                    >
                        <thead
                            class="bg-[#FBF8EA] text-xs font-semibold tracking-wide text-gray-600 uppercase"
                        >
                            <tr>
                                <th scope="col" class="px-6 py-3">Name</th>
                                <th scope="col" class="px-6 py-3">Email</th>
                                <th scope="col" class="px-6 py-3">Role</th>
                                <th scope="col" class="px-6 py-3">
                                    Municipality
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Registered
                                </th>
                                <th scope="col" class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="transition hover:bg-[#FBF8EA]/50"
                            >
                                <td
                                    class="px-6 py-4 font-semibold whitespace-nowrap text-[#14391f]"
                                >
                                    {{ user.name }}
                                    <span
                                        v-if="user.is_deactivated"
                                        class="ml-2 inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-800 ring-1 ring-rose-600/20 ring-inset"
                                    >
                                        Deactivated
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ user.email }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                        :class="roleClasses[user.role]"
                                    >
                                        {{ roleLabels[user.role] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-700 capitalize">
                                    {{ user.municipality ?? '—' }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-gray-600"
                                >
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <select
                                            v-if="
                                                user.id !== currentUserId &&
                                                user.role !== 'farmer'
                                            "
                                            :aria-label="`Change ${user.name}'s role`"
                                            :value="user.role"
                                            :disabled="
                                                processingUserId === user.id
                                            "
                                            class="cursor-pointer rounded-lg border-gray-300 text-xs shadow-sm focus:border-[#14391f] focus:ring-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                                            @change="changeRole(user, $event)"
                                        >
                                            <option :value="user.role" disabled>
                                                Current:
                                                {{ roleLabels[user.role] }}
                                            </option>
                                            <option
                                                v-if="user.role === 'admin'"
                                                value="lgu_staff"
                                            >
                                                Change to LGU Staff
                                            </option>
                                            <option v-else value="admin">
                                                Change to Admin
                                            </option>
                                        </select>
                                        <button
                                            v-if="user.id !== currentUserId"
                                            type="button"
                                            :disabled="
                                                processingUserId === user.id
                                            "
                                            class="cursor-pointer rounded-full border border-[#14391f] px-3 py-1.5 text-xs font-semibold text-[#14391f] transition hover:bg-[#FBF8EA] disabled:cursor-not-allowed disabled:opacity-50"
                                            @click="
                                                setDeactivated(
                                                    user,
                                                    !user.is_deactivated,
                                                )
                                            "
                                        >
                                            {{
                                                user.is_deactivated
                                                    ? 'Reactivate'
                                                    : 'Deactivate'
                                            }}
                                        </button>
                                        <button
                                            v-if="user.id !== currentUserId"
                                            type="button"
                                            :disabled="
                                                processingUserId === user.id
                                            "
                                            class="cursor-pointer rounded-full border border-[#14391f] px-3 py-1.5 text-xs font-semibold text-[#14391f] transition hover:bg-[#FBF8EA] disabled:cursor-not-allowed disabled:opacity-50"
                                            @click="openPasswordDialog(user)"
                                        >
                                            Reset password
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <nav
                v-if="users.links.length > 3"
                aria-label="Pagination"
                class="flex flex-wrap items-center justify-end gap-1"
            >
                <template v-for="(link, i) in users.links" :key="i">
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

            <dialog
                ref="passwordDialog"
                aria-labelledby="password-dialog-title"
                class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl bg-white p-0 shadow-xl backdrop:bg-black/40"
                @close="resetPasswordDialog"
                @cancel="handlePasswordDialogCancel"
            >
                <form
                    class="space-y-5 p-6"
                    @submit.prevent="submitPasswordReset"
                >
                    <div>
                        <h2
                            id="password-dialog-title"
                            class="text-lg font-semibold text-[#14391f]"
                        >
                            Reset password
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Set a new password for {{ passwordUser?.name }}.
                        </p>
                    </div>

                    <div>
                        <label
                            for="new-password"
                            class="block text-sm font-medium text-gray-700"
                        >
                            New password
                        </label>
                        <input
                            id="new-password"
                            v-model="passwordForm.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            :disabled="passwordForm.processing"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <p
                            v-if="passwordForm.errors.password"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="confirm-password"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Confirm password
                        </label>
                        <input
                            id="confirm-password"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            :disabled="passwordForm.processing"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#14391f] focus:ring-[#14391f] disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <p
                            v-if="passwordForm.errors.password_confirmation"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ passwordForm.errors.password_confirmation }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            :disabled="passwordForm.processing"
                            class="cursor-pointer rounded-full border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 transition hover:bg-[#FBF8EA] disabled:cursor-not-allowed disabled:opacity-50"
                            @click="passwordDialog?.close()"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="cursor-pointer rounded-full bg-[#14391f] px-5 py-2 text-sm font-semibold text-[#FBEB9A] transition hover:bg-[#0f2d19] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </dialog>
        </main>
    </AuthenticatedLayout>
</template>
