<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

interface Marker {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    description?: string;
    added: string;
    edited?: string;
}

const mapContainer = ref<HTMLElement>();
const map = ref<any>();
const markers = ref<Marker[]>([]);
const showForm = ref(false);
const saving = ref(false);
const newMarker = ref({
    name: '',
    latitude: 0,
    longitude: 0,
    description: '',
});

const mapMarkers = ref<any[]>([]);

const loadLeaflet = (): Promise<void> => {
    return new Promise((resolve, reject) => {
        if ((window as any).L) {
            resolve();
            return;
        }

        const existingScript = document.querySelector<HTMLScriptElement>(
            'script[data-leaflet="true"]',
        );
        if (existingScript && (window as any).L) {
            resolve();
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        link.integrity =
            'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
        link.crossOrigin = '';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.async = true;
        script.defer = true;
        script.setAttribute('data-leaflet', 'true');
        script.onload = () => resolve();
        script.onerror = () =>
            reject(new Error('Leaflet failed to load from CDN'));
        document.head.appendChild(script);
    });
};

const initMap = async () => {
    if (!mapContainer.value) {
        return;
    }

    try {
        await loadLeaflet();
        const L = (window as any).L;

        map.value = L.map(mapContainer.value).setView([59.437, 24.7536], 10);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map.value);

        map.value.on('click', (event: any) => {
            if (event.latlng) {
                showMarkerForm(event.latlng.lat, event.latlng.lng);
            }
        });

        await loadMarkers();
    } catch (error) {
        console.error('Error initializing map:', error);
    }
};

const loadMarkers = async () => {
    try {
        const response = await axios.get('/api/markers');
        markers.value = response.data;
        updateMapMarkers();
    } catch (error) {
        console.error('Error loading markers:', error);
    }
};

const updateMapMarkers = () => {
    const L = (window as any).L;

    // Clear existing markers from the map
    mapMarkers.value.forEach((markerInstance) => {
        try {
            markerInstance.remove();
        } catch {
            if (map.value) {
                map.value.removeLayer(markerInstance);
            }
        }
    });
    mapMarkers.value = [];

    if (!map.value || !L) {
        return;
    }

    // Add new markers
    markers.value.forEach((marker) => {
        const leafletMarker = L.marker([marker.latitude, marker.longitude])
            .addTo(map.value)
            .bindPopup(
                `
                <div>
                    <h3 class="font-bold">${marker.name}</h3>
                    <p>${marker.latitude.toFixed(6)}, ${marker.longitude.toFixed(
                    6,
                )}</p>
                    ${
                        marker.description
                            ? `<p>${marker.description}</p>`
                            : ''
                    }
                </div>
            `,
            );

        mapMarkers.value.push(leafletMarker);
    });
};

const showMarkerForm = (lat: number, lng: number) => {
    newMarker.value = {
        name: '',
        latitude: lat,
        longitude: lng,
        description: '',
    };
    showForm.value = true;
};

const saveMarker = async () => {
    saving.value = true;
    try {
        const response = await axios.post('/api/markers', newMarker.value);
        markers.value.push(response.data);
        updateMapMarkers();
        showForm.value = false;
        newMarker.value = {
            name: '',
            latitude: 0,
            longitude: 0,
            description: '',
        };
    } catch (error) {
        console.error('Error saving marker:', error);
        alert('Error saving marker');
    } finally {
        saving.value = false;
    }
};

const cancelForm = () => {
    showForm.value = false;
    newMarker.value = { name: '', latitude: 0, longitude: 0, description: '' };
};

const editMarker = (marker: Marker) => {
    newMarker.value = {
        name: marker.name,
        latitude: marker.latitude,
        longitude: marker.longitude,
        description: marker.description || '',
    };
    showForm.value = true;
    // For edit, we would need to implement update logic
    // For now, just show the form with existing data
};

const deleteMarker = async (marker: Marker) => {
    if (!confirm('Kas olete kindel, et soovite selle markeri kustutada?'))
        return;

    try {
        await axios.delete(`/api/markers/${marker.id}`);
        markers.value = markers.value.filter((m) => m.id !== marker.id);
        updateMapMarkers();
    } catch (error) {
        console.error('Error deleting marker:', error);
        alert('Viga markeri kustutamisel');
    }
};

onMounted(() => {
    nextTick(() => {
        initMap();
    });
});
</script>

<template>
    <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
        <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
            Map Widget
        </h2>

        <!-- Map Container -->
        <div class="mb-4">
            <div
                ref="mapContainer"
                class="h-96 w-full rounded-lg border border-gray-300 dark:border-gray-600"
            ></div>
        </div>

        <!-- Marker Form -->
        <div
            v-if="showForm"
            class="mb-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700"
        >
            <h3 class="mb-3 text-lg font-medium text-gray-900 dark:text-white">
                Add new marker
            </h3>
            <form @submit.prevent="saveMarker" class="space-y-3">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Name
                    </label>
                    <input
                        v-model="newMarker.name"
                        type="text"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                        placeholder="Marker name"
                    />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Latitude
                        </label>
                        <input
                            v-model="newMarker.latitude"
                            type="number"
                            step="any"
                            required
                            readonly
                            class="w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Longitude
                        </label>
                        <input
                            v-model="newMarker.longitude"
                            type="number"
                            step="any"
                            required
                            readonly
                            class="w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                        />
                    </div>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Description
                    </label>
                    <textarea
                        v-model="newMarker.description"
                        rows="3"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                        placeholder="Optional description"
                    ></textarea>
                </div>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="rounded-md bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:opacity-50"
                    >
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                    <button
                        type="button"
                        @click="cancelForm"
                        class="rounded-md bg-gray-500 px-4 py-2 text-white hover:bg-gray-600"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Markers List -->
        <div v-if="markers.length > 0" class="space-y-2">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Markers
            </h3>
            <div class="max-h-48 space-y-2 overflow-y-auto">
                <div
                    v-for="marker in markers"
                    :key="marker.id"
                    class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4
                                class="font-medium text-gray-900 dark:text-white"
                            >
                                {{ marker.name }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ marker.latitude.toFixed(6) }},
                                {{ marker.longitude.toFixed(6) }}
                            </p>
                            <p
                                v-if="marker.description"
                                class="mt-1 text-sm text-gray-700 dark:text-gray-300"
                            >
                                {{ marker.description }}
                            </p>
                        </div>
                        <div class="ml-2 flex gap-1">
                            <button
                                @click="editMarker(marker)"
                                class="rounded bg-yellow-500 px-2 py-1 text-xs text-white hover:bg-yellow-600"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteMarker(marker)"
                                class="rounded bg-red-500 px-2 py-1 text-xs text-white hover:bg-red-600"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="py-4 text-center text-gray-500 dark:text-gray-400">
            No markers added. Click on the map to add the first marker.
        </div>
    </div>
</template>
