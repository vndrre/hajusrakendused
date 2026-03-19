<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the blog index for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/blog')
        ->assertSuccessful()
        ->assertInertia(fn ($page): mixed => $page->component('Blog/Index'));
});

it('allows creating a post', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('blog.store'), [
            'title' => 'My first post',
            'description' => 'Hello world',
        ])
        ->assertRedirect(route('blog.index'));

    expect(Post::query()->count())->toBe(1);
});
