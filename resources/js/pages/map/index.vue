<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Map from 'ol/Map';
import View from 'ol/View';
import type { FeatureLike } from 'ol/Feature';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import VectorSource from 'ol/source/Vector';
import GeoJSON from 'ol/format/GeoJSON';
import OSM from 'ol/source/OSM';
import { fromLonLat, transformExtent } from 'ol/proj';
import { Fill, Stroke, Style } from 'ol/style';
import 'ol/ol.css';

interface OutbreakPin {
    id: number;
    disease: string;
    municipality: string;
    created_at: string | null;
}

const props = defineProps<{ outbreaks: OutbreakPin[] }>();

const mapEl = ref<HTMLDivElement>();
const hoveredSlug = ref<string | null>(null);
let resetView = () => {};

const names: Record<string, string> = {
    abucay: 'Abucay',
    bagac: 'Bagac',
    balanga: 'Balanga City',
    dinalupihan: 'Dinalupihan',
    hermosa: 'Hermosa',
    limay: 'Limay',
    mariveles: 'Mariveles',
    morong: 'Morong',
    orani: 'Orani',
    orion: 'Orion',
    pilar: 'Pilar',
    samal: 'Samal',
};

const hoveredOutbreaks = computed(() =>
    props.outbreaks.filter((o) => o.municipality === hoveredSlug.value),
);

onMounted(() => {
    const baseStyle = new Style({
        stroke: new Stroke({ color: '#374151', width: 1.5 }),
        fill: new Fill({ color: 'rgba(107, 114, 128, 0.08)' }),
    });

    const hoverStyle = new Style({
        stroke: new Stroke({ color: '#1d4ed8', width: 3 }),
        fill: new Fill({ color: 'rgba(59, 130, 246, 0.35)' }),
    });

    let hovered: FeatureLike | null = null;

    const boundaryLayer = new VectorLayer({
        source: new VectorSource({
            url: '/geo/bataan-municipalities.geojson',
            format: new GeoJSON(),
        }),
        style: (feature) => (feature === hovered ? hoverStyle : baseStyle),
    });

    const map = new Map({
        target: mapEl.value,
        layers: [
            new TileLayer({ source: new OSM() }),
            new VectorLayer({
                source: new VectorSource({
                    url: '/geo/bataan-mask.geojson',
                    format: new GeoJSON(),
                }),
                style: new Style({
                    fill: new Fill({ color: '#f3f4f6' }),
                }),
                updateWhileInteracting: true,
                updateWhileAnimating: true,
            }),
            boundaryLayer,
        ],
        view: new View({
            center: fromLonLat([120.45, 14.65]),
            zoom: 10,
            minZoom: 9,
            maxZoom: 19,
            extent: transformExtent(
                [120.1, 14.25, 120.75, 15.05],
                'EPSG:4326',
                'EPSG:3857',
            ),
            constrainOnlyCenter: true,
            smoothExtentConstraint: false,
        }),
    });

    resetView = () => {
        const extent = boundaryLayer.getSource()?.getExtent();
        if (extent) {
            map.getView().fit(extent, {
                padding: [30, 30, 30, 30],
                duration: 600,
            });
        }
    };

    boundaryLayer.getSource()?.once('featuresloadend', () => resetView());

    map.on('pointermove', (event) => {
        if (event.dragging) return;

        const feature = map.forEachFeatureAtPixel(event.pixel, (f) => f, {
            layerFilter: (layer) => layer === boundaryLayer,
        });

        if (feature !== hovered) {
            hovered = feature ?? null;
            hoveredSlug.value = hovered?.get('municipality') ?? null;
            boundaryLayer.changed();
            map.getTargetElement().style.cursor = hovered ? 'pointer' : '';
        }
    });

    map.on('click', (event) => {
        const feature = map.forEachFeatureAtPixel(event.pixel, (f) => f, {
            layerFilter: (layer) => layer === boundaryLayer,
        });

        const extent = feature?.getGeometry()?.getExtent();
        if (extent) {
            map.getView().fit(extent, {
                padding: [40, 40, 40, 40],
                duration: 600,
                maxZoom: 14,
            });
        }
    });
});
</script>

<template>
    <Head title="Outbreak Map" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Outbreak Map
            </h2>
        </template>

        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
            <div class="relative">
                <div ref="mapEl" class="h-128 w-full rounded-lg shadow"></div>

                <div
                    v-if="hoveredSlug"
                    class="pointer-events-none absolute top-4 right-4 w-56 rounded-lg bg-white p-4 shadow"
                >
                    <p class="font-semibold text-gray-800">
                        {{ names[hoveredSlug] }}
                    </p>
                    <p
                        v-if="hoveredOutbreaks.length"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ hoveredOutbreaks.length }} active outbreak(s)
                    </p>
                    <p v-else class="mt-1 text-sm text-gray-500">
                        No active outbreaks
                    </p>
                    <ul class="mt-1 text-sm text-gray-600">
                        <li v-for="o in hoveredOutbreaks" :key="o.id">
                            {{ o.disease }}
                        </li>
                    </ul>
                </div>

                <button
                    type="button"
                    class="absolute bottom-4 left-4 rounded-md bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow hover:bg-gray-50"
                    @click="resetView()"
                >
                    Reset view
                </button>
            </div>

            <p v-if="!outbreaks.length" class="mt-4 text-sm text-gray-500">
                No active outbreaks.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
