<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/ScanController';

interface Variety {
    id: number;
    name: string;
}

interface Farmer {
    id: number;
    full_name: string;
    barangay: string;
}

defineProps<{
    varieties: Variety[];
    farmers: Farmer[];
}>();

const page = usePage();
const isLgu = computed(() => page.props.auth.user.role === 'lgu_staff');

const form = useForm({
    image: null as File | null,
    scan_type: 'leaf',
    variety_id: null as number | null,
    farmer_id: null as number | null,
    gps_lat: null as number | null,
    gps_long: null as number | null,
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

            <div v-if="form.scan_type === 'leaf'">
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

            <template v-if="isLgu">
                <div>
                    <label for="farmer_id">Farmer</label>
                    <select id="farmer_id" v-model="form.farmer_id">
                        <option :value="null" disabled>Select farmer</option>
                        <option
                            v-for="farmer in farmers"
                            :key="farmer.id"
                            :value="farmer.id"
                        >
                            {{ farmer.full_name }} ({{ farmer.barangay }})
                        </option>
                    </select>
                    <div v-if="form.errors.farmer_id">
                        {{ form.errors.farmer_id }}
                    </div>
                </div>

                <div>
                    <label for="gps_lat">Latitude</label>
                    <input
                        id="gps_lat"
                        v-model="form.gps_lat"
                        type="number"
                        step="any"
                    />
                    <div v-if="form.errors.gps_lat">
                        {{ form.errors.gps_lat }}
                    </div>
                </div>

                <div>
                    <label for="gps_long">Longitude</label>
                    <input
                        id="gps_long"
                        v-model="form.gps_long"
                        type="number"
                        step="any"
                    />
                    <div v-if="form.errors.gps_long">
                        {{ form.errors.gps_long }}
                    </div>
                </div>
            </template>

            <button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Analyzing...' : 'Submit Scan' }}
            </button>
        </form>
    </div>
</template>
