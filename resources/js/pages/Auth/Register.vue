<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    municipalities: string[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    barangay: '',
    municipality: '',
    contact_number: '',
    farm_lat: '',
    farm_long: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4">
                <InputLabel for="barangay" value="Barangay" />
                <TextInput
                    id="barangay"
                    v-model="form.barangay"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.barangay" />
            </div>

            <div class="mt-4">
                <InputLabel for="municipality" value="Municipality" />
                <select
                    id="municipality"
                    v-model="form.municipality"
                    class="mt-1 block w-full rounded-md border-gray-300"
                    required
                >
                    <option value="" disabled>Select municipality</option>
                    <option v-for="m in municipalities" :key="m" :value="m">
                        {{ m }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.municipality" />
            </div>

            <div class="mt-4">
                <InputLabel for="contact_number" value="Contact number" />
                <TextInput
                    id="contact_number"
                    v-model="form.contact_number"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.contact_number"
                />
            </div>

            <div class="mt-4">
                <InputLabel for="farm_lat" value="Farm latitude" />
                <TextInput
                    id="farm_lat"
                    v-model="form.farm_lat"
                    type="number"
                    step="any"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.farm_lat" />
            </div>

            <div class="mt-4">
                <InputLabel for="farm_long" value="Farm longitude" />
                <TextInput
                    id="farm_long"
                    v-model="form.farm_long"
                    type="number"
                    step="any"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.farm_long" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
