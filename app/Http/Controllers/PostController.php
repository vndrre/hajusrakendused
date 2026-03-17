<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = Post::query()
            ->with(['user:id,name', 'comments.user:id,name'])
            ->latest()
            ->get();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'authUser' => $request->user()
                ? $request->user()->only(['id', 'name'])
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $request->user()->posts()->create($validated);

        return redirect()->route('blog.index');
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        if ($request->user()->id !== $post->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $post->update($validated);

        return redirect()->route('blog.index');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        if ($request->user()->id !== $post->user_id) {
            abort(403);
        }

        $post->delete();

        return redirect()->route('blog.index');
    }
}

