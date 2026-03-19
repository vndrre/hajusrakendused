<script setup lang="ts">
import { ref, onMounted, nextTick, onBeforeUnmount } from 'vue';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

interface Marker {
    id: number;
    name: string;
    latitude: number | string;
    longitude: number | string;
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
const toCoordinate = (value: number | string): number => Number(value) || 0;

const initMap = async () => {
    if (!mapContainer.value) {
        return;
    }

    try {
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

        setTimeout(() => {
            map.value?.invalidateSize();
        }, 0);

        await loadMarkers();
    } catch (error) {
        console.error('Error initializing map:', error);
    }
};

const loadMarkers = async () => {
    try {
        const response = await axios.get('/api/markers');
        markers.value = response.data.map((marker: Marker) => ({
            ...marker,
            latitude: toCoordinate(marker.latitude),
            longitude: toCoordinate(marker.longitude),
        }));
        updateMapMarkers();
    } catch (error) {
        console.error('Error loading markers:', error);
    }
};

const updateMapMarkers = () => {
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
        const latitude = toCoordinate(marker.latitude);
        const longitude = toCoordinate(marker.longitude);

        const leafletMarker = L.marker([latitude, longitude])
            .addTo(map.value)
            .bindPopup(
                `
                <div>
                    <h3 class="font-bold">${marker.name}</h3>
                    <p>${latitude.toFixed(6)}, ${longitude.toFixed(
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
        markers.value.push({
            ...response.data,
            latitude: toCoordinate(response.data.latitude),
            longitude: toCoordinate(response.data.longitude),
        });
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
        latitude: toCoordinate(marker.latitude),
        longitude: toCoordinate(marker.longitude),
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

onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});
</script>

<template>
    <div class="rounded-3xl p-6 shadow-xl h-full w-full">
        <h2 class="mb-4 text-2xl font-semibold text-zinc-900 dark:text-white">
            Map Widget
        </h2>


        <div class="grid gap-4 xl:grid-cols-3 h-full w-full">
                    <!-- Map Container -->
            <div class="mb-4 w-full xl:col-span-2">
                <div
                    ref="mapContainer"
                    class="h-full w-full rounded-2xl border border-zinc-300 dark:border-zinc-700"
                ></div>
            </div>

            <!-- Marker Form -->
            <div
                v-if="showForm"
                class="mb-4 w-full rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900"
            >
                <h3 class="mb-3 text-lg font-medium text-zinc-900 dark:text-white">
                    Add new marker
                </h3>
                <form @submit.prevent="saveMarker" class="space-y-3">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Name
                        </label>
                        <input
                            v-model="newMarker.name"
                            type="text"
                            required
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:focus:ring-zinc-500"
                            placeholder="Marker name"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                            >
                                Latitude
                            </label>
                            <input
                                v-model="newMarker.latitude"
                                type="number"
                                step="any"
                                required
                                readonly
                                class="w-full rounded-xl border border-zinc-300 bg-zinc-100 px-3 py-2 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                            >
                                Longitude
                            </label>
                            <input
                                v-model="newMarker.longitude"
                                type="number"
                                step="any"
                                required
                                readonly
                                class="w-full rounded-xl border border-zinc-300 bg-zinc-100 px-3 py-2 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                            />
                        </div>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Description
                        </label>
                        <textarea
                            v-model="newMarker.description"
                            rows="3"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:focus:ring-zinc-500"
                            placeholder="Optional description"
                        ></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="rounded-xl bg-zinc-900 px-4 py-2 text-white transition hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                        >
                            {{ saving ? 'Saving...' : 'Save' }}
                        </button>
                        <button
                            type="button"
                            @click="cancelForm"
                            class="rounded-xl bg-zinc-600 px-4 py-2 text-white transition hover:bg-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700"
                        >
                            Cancel
                        </button>
                    </div>
                </form>

                <!-- Markers List -->
                <div v-if="markers.length > 0" class="w-full space-y-2 mt-10">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                        Markers
                    </h3>
                <div class="space-y-2">
                        <div
                            v-for="marker in markers"
                            :key="marker.id"
                            class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 mx-2 dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4
                                        class="font-medium text-zinc-900 dark:text-white"
                                    >
                                        {{ marker.name }}
                                    </h4>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ toCoordinate(marker.latitude).toFixed(6) }},
                                        {{ toCoordinate(marker.longitude).toFixed(6) }}
                                    </p>
                                    <p
                                        v-if="marker.description"
                                        class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"
                                    >
                                        {{ marker.description }}
                                    </p>
                                </div>
                                <div class="ml-2 flex gap-1">
                                    <button
                                        @click="editMarker(marker)"
                                        class="rounded bg-zinc-700 px-2 py-1 text-xs text-white transition hover:bg-zinc-900 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteMarker(marker)"
                                        class="rounded bg-black px-2 py-1 text-xs text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-300"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div> 

            </div>


        </div>
    </div>
</template>
