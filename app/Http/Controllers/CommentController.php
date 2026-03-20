<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): \Symfony\Component\HttpFoundation\Response
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return Inertia::location(route('blog.index'));
    }

    public function destroy(Request $request, Comment $comment): \Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();

        if ($user->id !== $comment->user_id) {
            abort(403);
        }

        $comment->delete();

        return Inertia::location(route('blog.index'));
    }
}
