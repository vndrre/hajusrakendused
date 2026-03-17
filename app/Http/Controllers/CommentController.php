<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return redirect()->route('blog.index');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        if ($user->id !== $comment->user_id && $user->id !== $comment->post->user_id) {
            abort(403);
        }

        $comment->delete();

        return redirect()->route('blog.index');
    }
}

