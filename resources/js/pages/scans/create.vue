<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/ScanController';

interface Variety {
    id: number;
    name: string;
}

defineProps<{
    varieties: Variety[];
}>();

const form = useForm({
    image: null as File | null,
    scan_type: 'leaf',
    variety_id: null as number | null,
});

function handleImage(event: Event) {
    const target = event.target as HTMLInputElement;
    form.image = target.files?.[0] ?? null;
}

function submit() {
    form.post(store().url, { forceFormData: true });
}
</script>

<template>
    <Head title="New Scan" />

    <div>
        <h1>New Scan</h1>

        <form @submit.prevent="submit">
            <div>
                <label for="image">Photo</label>
                <input
                    id="image"
                    type="file"
                    accept="image/*"
                    @change="handleImage"
                />
                <div v-if="form.errors.image">{{ form.errors.image }}</div>
            </div>

            <div>
                <label for="scan_type">Scan Type</label>
                <select id="scan_type" v-model="form.scan_type">
                    <option value="leaf">Leaf</option>
                    <option value="grain">Grain</option>
                </select>
                <div v-if="form.errors.scan_type">
                    {{ form.errors.scan_type }}
                </div>
            </div>

            <div>
                <label for="variety_id">Variety (optional)</label>
                <select id="variety_id" v-model="form.variety_id">
                    <option :value="null">— none —</option>
                    <option
                        v-for="variety in varieties"
                        :key="variety.id"
                        :value="variety.id"
                    >
                        {{ variety.name }}
                    </option>
                </select>
                <div v-if="form.errors.variety_id">
                    {{ form.errors.variety_id }}
                </div>
            </div>

            <button type="submit" :disabled="form.processing">
                Submit Scan
            </button>
        </form>
    </div>
</template>
