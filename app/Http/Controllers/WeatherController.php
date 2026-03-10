<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WeatherController extends Controller
{
    public function __construct(
        protected WeatherService $weatherService
    ) {}

    public function show(Request $request)
    {
        $city = $request->get('city', 'Tallinn');
        $country = $request->get('country', 'EE');

        try {
            $weather = $this->weatherService->getWeather($city, $country);
            return Inertia::render('Weather', [
                'weather' => $weather,
                'city' => $city,
                'country' => $country,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Weather', [
                'error' => 'Unable to fetch weather data',
                'city' => $city,
                'country' => $country,
            ]);
        }
    }

    public function api(Request $request)
    {
        $request->validate([
            'city' => 'required|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        try {
            $weather = $this->weatherService->getWeather($request->city, $request->country ?? '');
            return response()->json($weather);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch weather data'], 500);
        }
    }
}