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
        <div class="mx-auto flex max-w-2xl flex-1 flex-col gap-4 p-4">
            <!-- Composer -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-500 text-sm font-semibold text-white"
                    >
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
                            class="w-full border-none bg-transparent text-base font-semibold text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0 dark:text-white"
                        />
                        <textarea
                            v-model="createPostForm.description"
                            rows="2"
                            placeholder="What's happening?"
                            class="w-full resize-none border-none bg-transparent text-sm text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-0 dark:text-gray-100"
                        />
                        <div class="flex items-center justify-between pt-2">
                            <div class="text-xs text-red-500">
                                <div
                                    v-if="createPostForm.errors.title"
                                    class="mb-1"
                                >
                                    {{ createPostForm.errors.title }}
                                </div>
                                <div v-if="createPostForm.errors.description">
                                    {{ createPostForm.errors.description }}
                                </div>
                            </div>
                            <button
                                type="submit"
                                :disabled="createPostForm.processing"
                                class="rounded-full bg-blue-500 px-4 py-1.5 text-sm font-semibold text-white shadow hover:bg-blue-600 disabled:opacity-50"
                            >
                                Post
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Feed -->
            <div
                class="divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white shadow-sm dark:divide-gray-800 dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    v-if="posts.length === 0"
                    class="p-6 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                    No posts yet. Be the first to share something.
                </div>
                <article
                    v-for="post in posts"
                    :key="post.id"
                    class="space-y-3 p-4"
                >
                    <div class="flex gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-100"
                        >
                            {{ post.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ post.user.name }}
                                    </span>
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ new Date(post.created_at).toLocaleString() }}
                                    </span>
                                </div>
                                <div
                                    v-if="authUser && authUser.id === post.user.id"
                                    class="flex gap-2 text-xs"
                                >
                                    <Link
                                        as="button"
                                        method="delete"
                                        :href="`/blog/${post.id}`"
                                        class="text-red-500 hover:underline"
                                    >
                                        Delete
                                    </Link>
                                </div>
                            </div>
                            <h3
                                class="mt-1 text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ post.title }}
                            </h3>
                            <p
                                class="mt-1 text-sm text-gray-800 dark:text-gray-100"
                            >
                                {{ post.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Comments -->
                    <div class="space-y-2 pl-12">
                        <div
                            v-for="comment in post.comments"
                            :key="comment.id"
                            class="flex items-start justify-between gap-2 text-sm"
                        >
                            <div>
                                <span
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ comment.user.name }}
                                </span>
                                <span
                                    class="ml-2 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        new Date(
                                            comment.created_at,
                                        ).toLocaleString()
                                    }}
                                </span>
                                <p
                                    class="mt-1 text-gray-800 dark:text-gray-100"
                                >
                                    {{ comment.body }}
                                </p>
                            </div>
                            <div
                                v-if="
                                    authUser &&
                                    (authUser.id === comment.user.id ||
                                        authUser.id === post.user.id)
                                "
                                class="ml-2 shrink-0 text-xs"
                            >
                                <Link
                                    as="button"
                                    method="delete"
                                    :href="`/comments/${comment.id}`"
                                    class="text-red-500 hover:underline"
                                >
                                    Delete
                                </Link>
                            </div>
                        </div>

                        <!-- Add comment -->
                        <form
                            v-if="authUser"
                            class="mt-2 flex items-center gap-2"
                            @submit.prevent="
                                getCommentForm(post.id).post(
                                    `/blog/${post.id}/comments`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () =>
                                            getCommentForm(post.id).reset(),
                                    },
                                )
                            "
                        >
                            <input
                                v-model="getCommentForm(post.id).body"
                                type="text"
                                placeholder="Add a comment..."
                                class="flex-1 rounded-full border border-gray-300 bg-transparent px-3 py-1 text-sm text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-100"
                            />
                            <button
                                type="submit"
                                :disabled="getCommentForm(post.id).processing"
                                class="rounded-full bg-blue-500 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-600 disabled:opacity-50"
                            >
                                Reply
                            </button>
                        </form>
                    </div>
                </article>
            </div>
        </div>
    </AppLayout>
</template>

