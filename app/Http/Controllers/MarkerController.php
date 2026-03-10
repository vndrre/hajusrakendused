<?php

namespace App\Http\Controllers;

use App\Models\Marker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class MarkerController extends Controller
{
    public function index()
    {
        return Inertia::render('Map', [
            'markers' => Marker::all(),
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        return response()->json(Marker::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'nullable|string|max:1000',
        ]);

        $marker = Marker::create([
            'name' => $validated['name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'description' => $validated['description'],
            'added' => now(),
        ]);

        return response()->json($marker, 201);
    }

    public function show(Marker $marker): JsonResponse
    {
        return response()->json($marker);
    }

    public function update(Request $request, Marker $marker): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'nullable|string|max:1000',
        ]);

        $marker->update([
            'name' => $validated['name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'description' => $validated['description'],
            'edited' => now(),
        ]);

        return response()->json($marker);
    }

    public function destroy(Marker $marker): JsonResponse
    {
        $marker->delete();
        return response()->json(['message' => 'Marker deleted successfully']);
    }
}
