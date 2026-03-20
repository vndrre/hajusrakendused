<?php

use App\Models\Comment;
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

it('allows the post author to delete their own post', function (): void {
    $user = User::factory()->create();
    $post = Post::query()->create([
        'user_id' => $user->id,
        'title' => 'My first post',
        'description' => 'Hello world',
    ]);

    $this->actingAs($user)
        ->delete(route('blog.destroy', $post))
        ->assertRedirect(route('blog.index'));

    expect(Post::query()->count())->toBe(0);
});

it('prevents other users from deleting a post', function (): void {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::query()->create([
        'user_id' => $author->id,
        'title' => 'My first post',
        'description' => 'Hello world',
    ]);

    $this->actingAs($otherUser)
        ->delete(route('blog.destroy', $post))
        ->assertForbidden();
});

it('allows the comment author to delete their own comment', function (): void {
    $author = User::factory()->create();
    $post = Post::query()->create([
        'user_id' => $author->id,
        'title' => 'My first post',
        'description' => 'Hello world',
    ]);

    $comment = $post->comments()->create([
        'user_id' => $author->id,
        'body' => 'Nice!',
    ]);

    $this->actingAs($author)
        ->delete(route('comments.destroy', $comment))
        ->assertRedirect(route('blog.index'));

    expect(Comment::query()->count())->toBe(0);
});

it('prevents the post author from deleting comments they did not write', function (): void {
    $postAuthor = User::factory()->create();
    $commentAuthor = User::factory()->create();

    $post = Post::query()->create([
        'user_id' => $postAuthor->id,
        'title' => 'My first post',
        'description' => 'Hello world',
    ]);

    $comment = $post->comments()->create([
        'user_id' => $commentAuthor->id,
        'body' => 'Nice!',
    ]);

    $this->actingAs($postAuthor)
        ->delete(route('comments.destroy', $comment))
        ->assertForbidden();
});
