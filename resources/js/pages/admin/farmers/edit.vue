<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    update as updateFarmer,
    show as showFarmer,
} from '@/actions/App/Http/Controllers/Admin/FarmerController';

interface FarmerForm {
    id: number;
    full_name: string;
    email: string;
    barangay: string;
    municipality: string;
    contact_number: string;
    plot_count: number | null;
    farm_lat: number | string;
    farm_long: number | string;
}

const props = defineProps<{
    farmer: FarmerForm;
    municipalities: string[];
}>();

const form = useForm({
    full_name: props.farmer.full_name,
    email: props.farmer.email,
    barangay: props.farmer.barangay,
    municipality: props.farmer.municipality,
    contact_number: props.farmer.contact_number,
    plot_count: String(props.farmer.plot_count ?? ''),
    farm_lat: String(props.farmer.farm_lat),
    farm_long: String(props.farmer.farm_long),
});

function submit(): void {
    form.put(updateFarmer(props.farmer.id).url);
}
</script>

<template>
    <Head :title="`Edit ${farmer.full_name}`" />

    <AuthenticatedLayout>
        <main class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-[#14391f]">
                        Edit farmer
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Update the farmer's registration and account details.
                    </p>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <InputLabel for="full_name" value="Full name" />
                        <TextInput
                            id="full_name"
                            v-model="form.full_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.full_name"
                        />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="barangay" value="Barangay" />
                            <TextInput
                                id="barangay"
                                v-model="form.barangay"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.barangay"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="municipality"
                                value="Municipality"
                            />
                            <select
                                id="municipality"
                                v-model="form.municipality"
                                class="mt-1 block w-full rounded-md border-gray-300"
                                required
                            >
                                <option
                                    v-for="town in municipalities"
                                    :key="town"
                                    :value="town"
                                >
                                    {{ town }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.municipality"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            for="contact_number"
                            value="Contact number"
                        />
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

                    <div>
                        <InputLabel for="plot_count" value="Number of plots" />
                        <TextInput
                            id="plot_count"
                            v-model="form.plot_count"
                            type="number"
                            min="1"
                            max="100"
                            step="1"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.plot_count"
                        />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="farm_lat" value="Farm latitude" />
                            <TextInput
                                id="farm_lat"
                                v-model="form.farm_lat"
                                type="number"
                                step="any"
                                min="14.3"
                                max="14.95"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.farm_lat"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="farm_long"
                                value="Farm longitude"
                            />
                            <TextInput
                                id="farm_long"
                                v-model="form.farm_long"
                                type="number"
                                step="any"
                                min="120.2"
                                max="120.7"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.farm_long"
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <PrimaryButton
                            variant="app"
                            :disabled="form.processing"
                        >
                            Save changes
                        </PrimaryButton>
                        <Link
                            :href="showFarmer(farmer.id).url"
                            class="text-sm font-medium text-gray-600 underline underline-offset-4 hover:text-[#14391f]"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
