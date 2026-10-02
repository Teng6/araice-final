<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Scan {
    id: number;
    scan_type: 'leaf' | 'grain';
    status: 'pending' | 'processing' | 'completed' | 'failed';
    image_url: string;
    confidence_score: number | null;
    gps_lat: number | null;
    gps_long: number | null;
    scan_date: string;
    disease: { id: number; name: string } | null;
    variety: { id: number; name: string } | null;
    farmer?: {
        id: number;
        full_name: string;
        contact_number: string | null;
    } | null;
}

defineProps<{ scan: Scan }>();
</script>

<template>
    <Head title="Scan Result" />

    <div>
        <h1>Scan #{{ scan.id }}</h1>

        <p v-if="scan.farmer">
            Farmer: {{ scan.farmer.full_name }}
            <span v-if="scan.farmer.contact_number">
                ({{ scan.farmer.contact_number }})
            </span>
        </p>

        <img :src="`/storage/${scan.image_url}`" alt="Scan image" width="320" />

        <p>Type: {{ scan.scan_type }}</p>
        <p>Status: {{ scan.status }}</p>

        <p v-if="scan.status === 'failed'">
            Analysis failed. Please try again.
        </p>

        <template v-if="scan.status === 'completed'">
            <p v-if="scan.scan_type === 'leaf'">
                Disease: {{ scan.disease?.name ?? 'No match found' }}
            </p>
            <p>Variety: {{ scan.variety?.name ?? '—' }}</p>
            <p v-if="scan.confidence_score !== null">
                Confidence: {{ (scan.confidence_score * 100).toFixed(1) }}%
            </p>
        </template>

        <p v-if="scan.gps_lat !== null">
            Location: {{ scan.gps_lat }}, {{ scan.gps_long }}
        </p>
        <p>Date: {{ scan.scan_date }}</p>
    </div>
</template>
