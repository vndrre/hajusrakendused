<?php

use App\Models\MyFavoriteSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('can create a book via JSON API', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $bookData = [
        'title' => 'Dune',
        'image' => 'https://example.com/dune.jpg',
        'description' => 'A science fiction novel.',
        'author' => 'Frank Herbert',
        'publication_year' => 1965,
    ];

    $response = $this->postJson('/api/books', $bookData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'title' => $bookData['title'],
            'author' => $bookData['author'],
            'publication_year' => $bookData['publication_year'],
        ]);

    $this->assertDatabaseHas('my_favorite_subject', [
        'user_id' => $user->id,
        ...$bookData,
    ]);
});

test('supports search, filtering, sorting, limit and caches responses', function (): void {
    Cache::flush();

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $bookA = MyFavoriteSubject::factory()->create([
        'user_id' => $user->id,
        'title' => 'Dune',
        'description' => 'A science fiction novel.',
        'author' => 'Frank Herbert',
        'publication_year' => 1965,
    ]);
    $bookB = MyFavoriteSubject::factory()->create([
        'user_id' => $user->id,
        'title' => 'Dune Messiah',
        'description' => 'Second novel in the series.',
        'author' => 'Frank Herbert',
        'publication_year' => 1969,
    ]);

    $bookC = MyFavoriteSubject::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Unknown Book',
        'description' => 'Something else.',
        'author' => 'Some Other Author',
        'publication_year' => 2001,
    ]);

    // Search + mine filter.
    $firstResponse = $this->getJson('/api/books?mine=1&q=Dune%20Messiah&limit=10&sort=created_at&direction=desc');
    $firstResponse->assertStatus(200)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.cached', false)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $bookB->id);

    // Same query again should be cached.
    $secondResponse = $this->getJson('/api/books?mine=1&q=Dune%20Messiah&limit=10&sort=created_at&direction=desc');
    $secondResponse->assertStatus(200)
        ->assertJsonPath('meta.cached', true);

    // Author filter + sort + limit.
    $thirdResponse = $this->getJson('/api/books?mine=0&author=Herbert&sort=publication_year&direction=desc&limit=1');
    $thirdResponse->assertStatus(200)
        ->assertJsonCount(1, 'data');

    // Between A (1965) and B (1969), B should be first for desc order.
    $thirdResponse->assertJsonPath('data.0.id', $bookB->id);
});
