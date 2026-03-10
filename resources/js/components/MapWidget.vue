<template>
    <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
        <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
            Kaardirakendus
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
                Lisa uus marker
            </h3>
            <form @submit.prevent="saveMarker" class="space-y-3">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Nimi
                    </label>
                    <input
                        v-model="newMarker.name"
                        type="text"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                        placeholder="Markeri nimi"
                    />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Laiuskraad
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
                            Pikkuskraad
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
                        Kirjeldus
                    </label>
                    <textarea
                        v-model="newMarker.description"
                        rows="3"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                        placeholder="Valikuline kirjeldus"
                    ></textarea>
                </div>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="rounded-md bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:opacity-50"
                    >
                        {{ saving ? 'Salvestan...' : 'Salvesta' }}
                    </button>
                    <button
                        type="button"
                        @click="cancelForm"
                        class="rounded-md bg-gray-500 px-4 py-2 text-white hover:bg-gray-600"
                    >
                        Tühista
                    </button>
                </div>
            </form>
        </div>

        <!-- Markers List -->
        <div v-if="markers.length > 0" class="space-y-2">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Markerid
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
                                Muuda
                            </button>
                            <button
                                @click="deleteMarker(marker)"
                                class="rounded bg-red-500 px-2 py-1 text-xs text-white hover:bg-red-600"
                            >
                                Kustuta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="py-4 text-center text-gray-500 dark:text-gray-400">
            Ühtegi markerit pole lisatud. Klõpsa kaardil, et lisada esimene
            marker.
        </div>
    </div>
</template>

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

const initMap = async () => {
    if (!mapContainer.value) return;

    // Load Radar Maps API
    if (!window.Radar) {
        await loadRadarMapsAPI();
    }

    // Initialize map
    map.value = new Radar.ui.Map(mapContainer.value, {
        center: { lat: 59.437, lng: 24.7536 }, // Tallinn coordinates
        zoom: 10,
        controls: {
            zoom: true,
            fullscreen: false,
            scale: false,
            geolocate: false,
        },
    });

    // Add click listener
    map.value.on('click', (event: any) => {
        if (event.latLng) {
            showMarkerForm(event.latLng.lat, event.latLng.lng);
        }
    });

    // Load existing markers
    await loadMarkers();
};

const loadRadarMapsAPI = (): Promise<void> => {
    return new Promise((resolve) => {
        if (window.Radar) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = `https://js.radar.com/v3/radar.js?key=${import.meta.env.VITE_RADAR_MAPS_API_KEY || 'YOUR_API_KEY'}`;
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        document.head.appendChild(script);
    });
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
    // Clear existing markers
    mapMarkers.value.forEach((marker) => {
        map.value.removeMarker(marker);
    });
    mapMarkers.value = [];

    // Add new markers
    markers.value.forEach((marker) => {
        const mapMarker = new Radar.ui.Marker({
            lat: marker.latitude,
            lng: marker.longitude,
            text: marker.name,
            color: '#3B82F6',
        });

        // Add popup
        mapMarker.on('click', () => {
            const popup = new Radar.ui.Popup({
                lat: marker.latitude,
                lng: marker.longitude,
                content: `
                    <div>
                        <h3 class="font-bold">${marker.name}</h3>
                        <p>${marker.latitude.toFixed(6)}, ${marker.longitude.toFixed(6)}</p>
                        ${marker.description ? `<p>${marker.description}</p>` : ''}
                    </div>
                `,
            });
            map.value.addPopup(popup);
        });

        map.value.addMarker(mapMarker);
        mapMarkers.value.push(mapMarker);
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
        alert('Viga markeri salvestamisel');
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
