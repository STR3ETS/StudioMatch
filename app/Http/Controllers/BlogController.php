<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{

    public function index(): View
    {
        $posts = Post::published()->with('sections')->orderByDesc('published_at')->get();

        return view('blog.index', [
            // Het nieuwste artikel krijgt de brede kaart bovenaan, de rest komt in het raster.
            'featured' => $posts->first(),
            'posts' => $posts->skip(1)->values(),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        // Een admin mag een concept of een ingepland artikel bekijken om het na te lopen
        // voordat het online gaat. Voor iedereen anders bestaat het nog niet.
        $preview = $request->user()?->isAdmin() ?? false;

        $post = Post::where('slug', $slug)
            ->unless($preview, fn ($query) => $query->published())
            ->with('sections')
            ->firstOrFail();

        return view('blog.show', [
            'post' => $post,
            'preview' => $preview && ! $post->isPublished(),
            'related' => Post::published()
                ->whereKeyNot($post->id)
                ->with('sections')
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
