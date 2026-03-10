<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import axios from 'axios';

    const searchCity = ref('Tallinn');
    const searchCountry = ref('EE');
    const weather = ref(null);
    const loading = ref(false);
    const error = ref('');

    const fetchWeather = async (city: string, country: string) => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.get('/api/weather', {
                params: { city, country }
            });
            weather.value = response.data;
        } catch (err) {
            error.value = 'Ilmaandmete hankimine ebaõnnestus';
            console.error(err);
        } finally {
            loading.value = false;
        }
    };

    const searchWeather = () => {
        if (searchCity.value.trim()) {
            fetchWeather(searchCity.value.trim(), searchCountry.value.trim());
        }
    };

    onMounted(() => {
        fetchWeather(searchCity.value, searchCountry.value);
    });
</script>

<template>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md w-full h-full">
        <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Weather Service</h2>

        <div class="mb-4">
            <form @submit.prevent="searchWeather" class="flex gap-2">
                <input
                    v-model="searchCity"
                    type="text"
                    placeholder="Insert city (for example: Tallinn)"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                />
                <input
                    v-model="searchCountry"
                    type="text"
                    placeholder="Riik (nt EE)"
                    class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                />
                <button
                    type="submit"
                    :disabled="loading"
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50 cursor-pointer"
                >
                    Search
                </button>
            </form>
        </div>

        <div v-if="loading" class="text-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
        </div>

        <div v-else-if="error" class="text-red-500 text-center py-4">
            {{ error }}
        </div>

        <div v-else-if="weather" class="text-center flex gap-5">
            <div class="flex items-center justify-center mb-4">
                <img
                    :src="`https://openweathermap.org/img/wn/${weather.weather[0].icon}@2x.png`"
                    :alt="weather.weather[0].description"
                    class="w-16 h-16"
                />
            </div>
            <div class="grid grid-cols-1 gap-2">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ weather.name }}, {{ weather.sys.country }}
                </h3>

                <div class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ weather.weather[0].description }}
                </div>
            </div>


            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-semibold">Temperature:</span>
                    {{ Math.round(weather.main.temp) }}°C
                </div>
                <div>
                    <span class="font-semibold">Feels Like:</span>
                    {{ Math.round(weather.main.feels_like) }}°C
                </div>
                <div>
                    <span class="font-semibold">Humidity:</span>
                    {{ weather.main.humidity }}%
                </div>
                <div>
                    <span class="font-semibold">Wind:</span>
                    {{ weather.wind.speed }} m/s
                </div>
            </div>
        </div>

        <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
            Insert city, to see weather information
        </div>
    </div>
</template>

