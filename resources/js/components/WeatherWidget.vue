<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import axios from 'axios';

    interface WeatherData {
        name: string;
        sys: { country: string };
        weather: Array<{ icon: string; description: string }>;
        main: { temp: number; feels_like: number; humidity: number };
        wind: { speed: number };
        clouds?: { all: number };
    }

    const searchCity = ref('Tallinn');
    const searchCountry = ref('EE');
    const weather = ref<WeatherData | null>(null);
    const loading = ref(false);
    const error = ref('');
    const countryOptions = [
        { code: 'EE', name: 'Estonia' },
        { code: 'FI', name: 'Finland' },
        { code: 'SE', name: 'Sweden' },
        { code: 'LV', name: 'Latvia' },
        { code: 'LT', name: 'Lithuania' },
        { code: 'PL', name: 'Poland' },
        { code: 'DE', name: 'Germany' },
        { code: 'GB', name: 'United Kingdom' },
        { code: 'US', name: 'United States' },
        { code: 'JP', name: 'Japan' },
    ];

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
    <div class="relative h-full w-full overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-100 p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
        <div
            class="h-full rounded-[calc(theme(borderRadius.3xl)-2px)] bg-white p-6 dark:bg-zinc-950"
        >
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Live weather</p>
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Forecast Overview</h2>
                </div>
                <div class="hidden rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 sm:block">
                    OpenWeather
                </div>
            </div>

            <form @submit.prevent="searchWeather" class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto]">
                <input
                    v-model="searchCity"
                    type="text"
                    placeholder="City (for example: Tallinn)"
                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-zinc-400 focus:outline-none focus:ring-4 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-zinc-500 dark:focus:ring-zinc-500/30"
                />
                <select
                    v-model="searchCountry"
                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-zinc-400 focus:outline-none focus:ring-4 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-zinc-500 dark:focus:ring-zinc-500/30 sm:w-52"
                >
                    <option
                        v-for="country in countryOptions"
                        :key="country.code"
                        :value="country.code"
                    >
                        {{ country.name }} ({{ country.code }})
                    </option>
                </select>
                <button
                    type="submit"
                    :disabled="loading"
                    class="cursor-pointer rounded-xl bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-zinc-900/20 transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                >
                    Search
                </button>
            </form>

            <div v-if="loading" class="flex min-h-52 items-center justify-center">
                <div class="h-10 w-10 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-900 dark:border-zinc-700 dark:border-t-zinc-100"></div>
            </div>

            <div v-else-if="error" class="rounded-2xl border border-zinc-300 bg-zinc-100 p-4 text-center text-sm font-medium text-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                {{ error }}
            </div>

            <div v-else-if="weather" class="space-y-6">
                <div class="flex flex-col gap-4 rounded-2xl bg-zinc-100 p-4 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl bg-white p-2 shadow-sm dark:bg-zinc-950">
                            <img
                                :src="`https://openweathermap.org/img/wn/${weather.weather[0].icon}@2x.png`"
                                :alt="weather.weather[0].description"
                                class="h-14 w-14"
                            />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white">
                                {{ weather.name }}, {{ weather.sys.country }}
                            </h3>
                            <p class="text-sm capitalize text-zinc-500 dark:text-zinc-300">
                                {{ weather.weather[0].description }}
                            </p>
                        </div>
                    </div>

                    <div class="text-left sm:text-right">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Current temp</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white">{{ Math.round(weather.main.temp) }}°C</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Feels like</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ Math.round(weather.main.feels_like) }}°C
                        </p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Humidity</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ weather.main.humidity }}%
                        </p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Wind</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ weather.wind.speed }} m/s
                        </p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Clouds</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ weather.clouds?.all ?? 0 }}%
                        </p>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-100 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                Insert city, to see weather information
            </div>
        </div>
    </div>
</template>

