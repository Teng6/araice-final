<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Map from 'ol/Map';
import View from 'ol/View';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import VectorSource from 'ol/source/Vector';
import OSM from 'ol/source/OSM';
import Feature from 'ol/Feature';
import Point from 'ol/geom/Point';
import { fromLonLat } from 'ol/proj';
import { Circle as CircleStyle, Fill, Stroke, Style } from 'ol/style';
import 'ol/ol.css';

interface OutbreakPin {
    id: number;
    disease: string;
    municipality: string;
    created_at: string | null;
}

const props = defineProps<{ outbreaks: OutbreakPin[] }>();

// [lng, lat] approximate town centers. Keys must match MunicipalityEnum values.
const centers: Record<string, [number, number]> = {
    Abucay: [120.5333, 14.7219],
    Bagac: [120.3947, 14.5978],
    'Balanga City': [120.536, 14.676],
    Dinalupihan: [120.4667, 14.8683],
    Hermosa: [120.5036, 14.8317],
    Limay: [120.5983, 14.5622],
    Mariveles: [120.485, 14.435],
    Morong: [120.2667, 14.6833],
    Orani: [120.5333, 14.8],
    Orion: [120.5753, 14.6203],
    Pilar: [120.5667, 14.6667],
    Samal: [120.5436, 14.7683],
};

const mapEl = ref<HTMLDivElement>();
const selected = ref<OutbreakPin | null>(null);

onMounted(() => {
    const features = props.outbreaks
        .filter((o) => centers[o.municipality])
        .map((o) => {
            const feature = new Feature({
                geometry: new Point(fromLonLat(centers[o.municipality])),
            });
            feature.set('outbreak', o);
            return feature;
        });

    const map = new Map({
        target: mapEl.value,
        layers: [
            new TileLayer({ source: new OSM() }),
            new VectorLayer({
                source: new VectorSource({ features }),
                style: new Style({
                    image: new CircleStyle({
                        radius: 10,
                        fill: new Fill({ color: 'rgba(220, 38, 38, 0.8)' }),
                        stroke: new Stroke({ color: '#fff', width: 2 }),
                    }),
                }),
            }),
        ],
        view: new View({ center: fromLonLat([120.5, 14.65]), zoom: 10 }),
    });

    map.on('click', (event) => {
        const feature = map.forEachFeatureAtPixel(event.pixel, (f) => f);
        selected.value = feature ? feature.get('outbreak') : null;
    });
});
</script>

<template>
    <Head title="Outbreak Map" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Outbreak Map
            </h2>
        </template>

        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
            <div ref="mapEl" class="h-[32rem] w-full rounded-lg shadow"></div>

            <p v-if="!outbreaks.length" class="mt-4 text-sm text-gray-500">
                No active outbreaks.
            </p>

            <div v-if="selected" class="mt-4 rounded-lg bg-white p-4 shadow">
                <p class="font-semibold text-gray-800">
                    {{ selected.disease }}
                </p>
                <p class="text-sm text-gray-600">{{ selected.municipality }}</p>
                <p class="text-sm text-gray-500">
                    Since {{ selected.created_at }}
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
