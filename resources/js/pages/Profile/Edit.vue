<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    farmerPlotCount: number | null;
}>();
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="space-y-6">
                <section class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :farmer-plot-count="farmerPlotCount"
                    />
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                    <UpdatePasswordForm />
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                    <DeleteUserForm />
                </section>
            </div>
        </main>
    </AuthenticatedLayout>
</template>
