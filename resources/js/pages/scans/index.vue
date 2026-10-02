<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { create, show } from '@/actions/App/Http/Controllers/ScanController';

interface Scan {
    id: number;
    scan_type: 'leaf' | 'grain';
    status: 'pending' | 'processing' | 'completed' | 'failed';
    confidence_score: number | null;
    scan_date: string;
    disease: { id: number; name: string } | null;
    variety: { id: number; name: string } | null;
    farmer?: {
        id: number;
        full_name: string;
        contact_number: string | null;
    } | null;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    scans: { data: Scan[]; links: PageLink[] };
}>();
</script>

<template>
    <Head title="Scans" />

    <div>
        <h1>Scans</h1>
        <Link :href="create().url">New Scan</Link>

        <p v-if="scans.data.length === 0">No scans yet.</p>

        <table v-else>
            <thead>
                <tr>
                    <th>ID</th>
                    <th v-if="scans.data[0]?.farmer !== undefined">Farmer</th>
                    <th>Type</th>
                    <th>Result</th>
                    <th>Confidence</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="scan in scans.data" :key="scan.id">
                    <td>
                        <Link :href="show(scan.id).url">#{{ scan.id }}</Link>
                    </td>
                    <td v-if="scan.farmer !== undefined">
                        {{ scan.farmer?.full_name ?? '—' }}
                        <span v-if="scan.farmer?.contact_number">
                            ({{ scan.farmer.contact_number }})
                        </span>
                    </td>
                    <td>{{ scan.scan_type }}</td>
                    <td>
                        {{
                            scan.scan_type === 'leaf'
                                ? (scan.disease?.name ?? '—')
                                : (scan.variety?.name ?? '—')
                        }}
                    </td>
                    <td>
                        {{
                            scan.confidence_score !== null
                                ? (scan.confidence_score * 100).toFixed(1) + '%'
                                : '—'
                        }}
                    </td>
                    <td>{{ scan.status }}</td>
                    <td>{{ scan.scan_date }}</td>
                </tr>
            </tbody>
        </table>

        <nav>
            <template v-for="(link, i) in scans.links" :key="i">
                <Link v-if="link.url" :href="link.url" v-html="link.label" />
                <span v-else v-html="link.label" />
                {{ ' ' }}
            </template>
        </nav>
    </div>
</template>
