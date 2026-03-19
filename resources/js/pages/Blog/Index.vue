<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

interface User {
    id: number;
    name: string;
}

interface Comment {
    id: number;
    body: string;
    created_at: string;
    user: User;
}

interface Post {
    id: number;
    title: string;
    description: string;
    created_at: string;
    user: User;
    comments: Comment[];
}

const props = defineProps<{
    posts: Post[];
    authUser: User | null;
}>();

const createPostForm = useForm({
    title: '',
    description: '',
});

const commentForms = new Map<number, ReturnType<typeof useForm>>();

const getCommentForm = (postId: number) => {
    if (!commentForms.has(postId)) {
        commentForms.set(
            postId,
            useForm({
                body: '',
            }),
        );
    }

    return commentForms.get(postId)!;
};
</script>

<template>
    <Head title="Blog" />

    <AppLayout>
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-4 p-4">
            <div class="h-full rounded-3xl border border-zinc-200 bg-zinc-100 p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="h-full rounded-[calc(theme(borderRadius.3xl)-2px)] bg-white p-4 dark:bg-zinc-950">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Blog</p>
                            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Community posts</h2>
                        </div>
                        <div class="hidden rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 sm:block">
                            Share updates & discuss
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Composer -->
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white dark:bg-zinc-100 dark:text-zinc-900">
                                    {{ authUser?.name?.charAt(0).toUpperCase() ?? 'U' }}
                                </div>
                                <form
                                    class="flex-1 space-y-3"
                                    @submit.prevent="
                                        createPostForm.post('/blog', {
                                            preserveScroll: true,
                                            onSuccess: () => createPostForm.reset(),
                                        })
                                    "
                                >
                                    <input
                                        v-model="createPostForm.title"
                                        type="text"
                                        placeholder="Post title"
                                        class="w-full border-none bg-transparent text-base font-semibold text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-0 dark:text-white"
                                    />
                                    <textarea
                                        v-model="createPostForm.description"
                                        rows="2"
                                        placeholder="What's happening?"
                                        class="w-full resize-none border-none bg-transparent text-sm text-zinc-900 placeholder:text-zinc-500 focus:outline-none focus:ring-0 dark:text-zinc-100"
                                    />
                                    <div class="flex items-center justify-between pt-2">
                                        <div class="min-h-[18px] text-xs text-red-600 dark:text-red-400">
                                            <div v-if="createPostForm.errors.title" class="mb-1">
                                                {{ createPostForm.errors.title }}
                                            </div>
                                            <div v-else-if="createPostForm.errors.description">
                                                {{ createPostForm.errors.description }}
                                            </div>
                                        </div>
                                        <button
                                            type="submit"
                                            :disabled="createPostForm.processing"
                                            class="rounded-full cursor-pointer bg-zinc-900 px-4 py-1.5 text-sm font-semibold text-white shadow hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                                        >
                                            Post
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Feed -->
                        <div class="divide-y divide-zinc-200 rounded-2xl border border-zinc-200 bg-white shadow-sm dark:divide-zinc-800 dark:border-zinc-700 dark:bg-zinc-900">
                            <div
                                v-if="posts.length === 0"
                                class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                            >
                                No posts yet. Be the first to share something.
                            </div>

                            <article
                                v-for="post in posts"
                                :key="post.id"
                                class="space-y-3 p-4"
                            >
                                <div class="flex gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-sm font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100">
                                        {{ post.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ post.user.name }}</span>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ new Date(post.created_at).toLocaleString() }}</span>
                                            </div>
                                            <div v-if="authUser && authUser.id === post.user.id" class="flex gap-2 text-xs">
                                                <Link
                                                    as="button"
                                                    method="delete"
                                                    :href="`/blog/${post.id}`"
                                                    class="rounded-md cursor-pointer px-2 py-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                                                >
                                                    Delete
                                                </Link>
                                            </div>
                                        </div>

                                        <h3 class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ post.title }}</h3>
                                        <p class="mt-1 text-sm text-zinc-800 dark:text-zinc-100">{{ post.description }}</p>
                                    </div>
                                </div>

                                <!-- Comments -->
                                <div class="space-y-3 border-l-2 border-zinc-200 pl-4 dark:border-zinc-700">
                                    <div
                                        v-for="comment in post.comments"
                                        :key="comment.id"
                                        class="flex items-start justify-between gap-2 text-sm"
                                    >
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-zinc-900 dark:text-white">{{ comment.user.name }}</span>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ new Date(comment.created_at).toLocaleString() }}</span>
                                            </div>
                                            <p class="mt-1 text-zinc-800 dark:text-zinc-100">{{ comment.body }}</p>
                                        </div>

                                        <div
                                            v-if="authUser && (authUser.id === comment.user.id || authUser.id === post.user.id)"
                                            class="ml-2 shrink-0 text-xs"
                                        >
                                            <Link
                                                as="button"
                                                method="delete"
                                                :href="`/comments/${comment.id}`"
                                                class="rounded-md cursor-pointer px-2 py-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                                            >
                                                Delete
                                            </Link>
                                        </div>
                                    </div>

                                    <!-- Add comment -->
                                    <form
                                        v-if="authUser"
                                        class="pt-1 flex items-center gap-2"
                                        @submit.prevent="
                                            getCommentForm(post.id).post(
                                                `/blog/${post.id}/comments`,
                                                {
                                                    preserveScroll: true,
                                                    onSuccess: () => getCommentForm(post.id).reset(),
                                                },
                                            )
                                        "
                                    >
                                        <input
                                            v-model="getCommentForm(post.id).body"
                                            type="text"
                                            placeholder="Add a comment..."
                                            class="flex-1 rounded-full border border-zinc-300 bg-transparent px-3 py-1.5 text-sm text-zinc-900 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:border-zinc-700 dark:text-zinc-100"
                                        />
                                        <button
                                            type="submit"
                                            :disabled="getCommentForm(post.id).processing"
                                            class="rounded-full cursor-pointer bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                                        >
                                            Reply
                                        </button>
                                    </form>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

