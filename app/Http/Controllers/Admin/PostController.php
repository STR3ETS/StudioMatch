<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PostController extends Controller
{

    public function index(Request $request): View
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::enum(PostStatus::class)],
        ])['status'] ?? null;

        return view('admin.posts.index', [
            'posts' => Post::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->withCount('sections')
                ->with('author')
                ->latest('updated_at')
                ->get(),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.form', ['post' => new Post(['status' => PostStatus::Concept])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePost($request);

        $post = new Post($this->attributes($request, $validated, new Post()));
        $post->user_id = $request->user()->id;
        $post->save();

        $this->syncSections($post, $validated['sections'] ?? []);

        return redirect()->route('admin.posts.edit', $post)->with('status', __('admin.blog.created'));
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', ['post' => $post->load('sections')]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $validated = $this->validatePost($request, $post);

        $post->update($this->attributes($request, $validated, $post));

        $this->syncSections($post, $validated['sections'] ?? []);

        return redirect()->route('admin.posts.edit', $post)->with('status', __('admin.blog.saved'));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $title = $post->title;

        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', __('admin.blog.deleted', ['title' => $title]));
    }

    private function validatePost(Request $request, ?Post $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => [
                'nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')->ignore($post),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_cover' => ['nullable', 'boolean'],
            'sections' => ['nullable', 'array', 'max:50'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.title' => ['nullable', 'string', 'max:200'],
            'sections.*.body' => ['nullable', 'string', 'max:50000'],
        ], [], [
            'title' => __('admin.blog.field_title'),
            'slug' => __('admin.blog.field_slug'),
            'cover' => __('admin.blog.field_cover'),
        ]);
    }

    private function attributes(Request $request, array $validated, Post $post): array
    {
        $attributes = [
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
        ];

        // Publiceren zonder datum betekent: nu live. Terug naar concept laat de datum staan,
        // zodat je hem bij opnieuw publiceren niet kwijt bent.
        if ($attributes['status'] === PostStatus::Gepubliceerd->value && $attributes['published_at'] === null) {
            $attributes['published_at'] = $post->published_at ?? now();
        }

        if ($request->hasFile('cover')) {
            $previous = $post->cover_path;
            $attributes['cover_path'] = $request->file('cover')->store('posts', 'public');

            if ($previous) {
                Storage::disk('public')->delete($previous);
            }
        } elseif ($request->boolean('remove_cover') && $post->cover_path) {
            Storage::disk('public')->delete($post->cover_path);
            $attributes['cover_path'] = null;
        }

        return $attributes;
    }

    /**
     * De alinea's komen als één lijst binnen; de volgorde in het formulier is de volgorde
     * op de pagina. Bestaande alinea's houden hun id, weggehaalde rijen verdwijnen.
     */
    private function syncSections(Post $post, array $rows): void
    {
        $keep = [];
        $order = 0;

        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $body = RichText::clean($row['body'] ?? null);

            if ($title === '' && $body === '') {
                continue;
            }

            $attributes = ['title' => $title ?: null, 'body' => $body ?: null, 'sort_order' => $order++];

            $section = isset($row['id']) ? $post->sections()->whereKey($row['id'])->first() : null;

            if ($section) {
                $section->update($attributes);
            } else {
                $section = $post->sections()->create($attributes);
            }

            $keep[] = $section->id;
        }

        $post->sections()->whereKeyNot($keep)->delete();
    }
}
