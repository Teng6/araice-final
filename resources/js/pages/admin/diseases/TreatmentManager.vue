<script setup lang="ts">
import {
    destroy as destroyTreatment,
    store as storeTreatment,
    update as updateTreatment,
} from '@/actions/App/Http/Controllers/Admin/TreatmentController';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Treatment, TreatmentType } from '@/types';

const props = defineProps<{
    diseaseId: number;
    treatments?: Treatment[];
}>();

const treatmentTypes: { value: TreatmentType; label: string }[] = [
    { value: 'chemical', label: 'Chemical' },
    { value: 'biological', label: 'Biological' },
    { value: 'cultural', label: 'Cultural' },
    { value: 'organic', label: 'Organic' },
];

const addForm = useForm({
    title: '',
    description: '',
    type: '' as TreatmentType | '',
});

const editForm = useForm({
    title: '',
    description: '',
    type: '' as TreatmentType | '',
});

const deleteForm = useForm<Record<string, string>>({});
const editingTreatmentId = ref<number | null>(null);

function addTreatment() {
    addForm.submit(storeTreatment({ disease: props.diseaseId }), {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    });
}

function startEditing(treatment: Treatment) {
    editingTreatmentId.value = treatment.id;
    editForm.title = treatment.title;
    editForm.description = treatment.description;
    editForm.type = treatment.type;
    editForm.clearErrors();
}

function cancelEditing() {
    editingTreatmentId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function saveTreatment() {
    const treatmentId = editingTreatmentId.value;
    if (treatmentId === null) {
        return;
    }

    editForm.submit(updateTreatment({ treatment: treatmentId }), {
        preserveScroll: true,
        onSuccess: () => {
            editingTreatmentId.value = null;
            editForm.reset();
        },
    });
}

function removeTreatment(treatment: Treatment) {
    if (!window.confirm(`Delete the treatment "${treatment.title}"?`)) {
        return;
    }

    deleteForm.delete(destroyTreatment({ treatment: treatment.id }).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <section
        class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
        aria-labelledby="treatments-heading"
    >
        <div>
            <h2
                id="treatments-heading"
                class="text-lg font-semibold text-gray-900"
            >
                Treatments
            </h2>
            <p v-if="!treatments?.length" class="mt-2 text-sm text-gray-500">
                No treatments have been added for this disease.
            </p>

            <ul v-else class="mt-4 divide-y divide-gray-100">
                <li
                    v-for="treatment in treatments"
                    :key="treatment.id"
                    class="py-4 first:pt-0 last:pb-0"
                >
                    <div
                        v-if="editingTreatmentId !== treatment.id"
                        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-medium text-gray-900">
                                    {{ treatment.title }}
                                </h3>
                                <span
                                    class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"
                                >
                                    {{
                                        treatmentTypes.find(
                                            (option) =>
                                                option.value === treatment.type,
                                        )?.label
                                    }}
                                </span>
                            </div>
                            <p
                                class="mt-2 text-sm whitespace-pre-line text-gray-700"
                            >
                                {{ treatment.description }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                class="rounded-md px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50"
                                @click="startEditing(treatment)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                :disabled="deleteForm.processing"
                                class="rounded-md px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="removeTreatment(treatment)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <form
                        v-else
                        class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4"
                        @submit.prevent="saveTreatment"
                    >
                        <div>
                            <label
                                :for="`edit-treatment-title-${treatment.id}`"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Title
                            </label>
                            <input
                                :id="`edit-treatment-title-${treatment.id}`"
                                v-model="editForm.title"
                                type="text"
                                required
                                maxlength="255"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                            />
                            <p
                                v-if="editForm.errors.title"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ editForm.errors.title }}
                            </p>
                        </div>
                        <div>
                            <label
                                :for="`edit-treatment-description-${treatment.id}`"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Description
                            </label>
                            <textarea
                                :id="`edit-treatment-description-${treatment.id}`"
                                v-model="editForm.description"
                                required
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                            ></textarea>
                            <p
                                v-if="editForm.errors.description"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ editForm.errors.description }}
                            </p>
                        </div>
                        <div>
                            <label
                                :for="`edit-treatment-type-${treatment.id}`"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Type
                            </label>
                            <select
                                :id="`edit-treatment-type-${treatment.id}`"
                                v-model="editForm.type"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                            >
                                <option value="" disabled>
                                    Select treatment type
                                </option>
                                <option
                                    v-for="option in treatmentTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <p
                                v-if="editForm.errors.type"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ editForm.errors.type }}
                            </p>
                        </div>
                        <div class="flex items-center justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                                @click="cancelEditing"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="inline-flex items-center rounded-md bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Save
                            </button>
                        </div>
                    </form>
                </li>
            </ul>
        </div>

        <form
            class="space-y-4 border-t border-gray-100 pt-5"
            @submit.prevent="addTreatment"
        >
            <h3 class="font-medium text-gray-900">Add treatment</h3>
            <div>
                <label
                    for="new-treatment-title"
                    class="block text-sm font-medium text-gray-700"
                >
                    Title
                </label>
                <input
                    id="new-treatment-title"
                    v-model="addForm.title"
                    type="text"
                    required
                    maxlength="255"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                />
                <p
                    v-if="addForm.errors.title"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ addForm.errors.title }}
                </p>
            </div>
            <div>
                <label
                    for="new-treatment-description"
                    class="block text-sm font-medium text-gray-700"
                >
                    Description
                </label>
                <textarea
                    id="new-treatment-description"
                    v-model="addForm.description"
                    required
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                ></textarea>
                <p
                    v-if="addForm.errors.description"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ addForm.errors.description }}
                </p>
            </div>
            <div>
                <label
                    for="new-treatment-type"
                    class="block text-sm font-medium text-gray-700"
                >
                    Type
                </label>
                <select
                    id="new-treatment-type"
                    v-model="addForm.type"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                >
                    <option value="" disabled>Select treatment type</option>
                    <option
                        v-for="option in treatmentTypes"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p v-if="addForm.errors.type" class="mt-1 text-sm text-red-600">
                    {{ addForm.errors.type }}
                </p>
            </div>
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="addForm.processing"
                    class="inline-flex items-center rounded-md bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Add treatment
                </button>
            </div>
        </form>
    </section>
</template>
