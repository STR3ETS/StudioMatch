<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    private function article(array $attributes = [], array $sections = []): Post
    {
        $post = Post::create(array_merge([
            'title' => 'Zo kies je de juiste opnamestudio',
            'excerpt' => 'Waar let je op als je voor het eerst een studio boekt?',
            'status' => PostStatus::Gepubliceerd,
            'published_at' => now()->subDay(),
        ], $attributes));

        foreach ($sections ?: [['title' => 'Begin bij je budget', 'body' => '<p>Bepaal eerst je <strong>maximum</strong>.</p>']] as $order => $section) {
            $post->sections()->create($section + ['sort_order' => $order]);
        }

        return $post->load('sections');
    }

    public function test_the_overview_shows_published_posts_only(): void
    {
        $this->article(['title' => 'Live artikel']);
        $this->article(['title' => 'Concept artikel', 'status' => PostStatus::Concept, 'published_at' => null]);
        $this->article(['title' => 'Ingepland artikel', 'published_at' => now()->addWeek()]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Live artikel')
            ->assertDontSee('Concept artikel')
            ->assertDontSee('Ingepland artikel');
    }

    public function test_the_overview_shows_an_empty_state_without_posts(): void
    {
        $this->get('/blog')->assertOk()->assertSee(__('blog.empty'));
    }

    public function test_the_newest_post_is_featured_and_the_rest_sit_in_the_grid(): void
    {
        $this->article(['title' => 'Ouder artikel', 'published_at' => now()->subMonth()]);
        $this->article(['title' => 'Nieuwer artikel', 'published_at' => now()->subHour()]);

        $html = $this->get('/blog')->assertOk()->getContent();

        $this->assertStringContainsString(__('blog.featured'), $html);
        $this->assertLessThan(
            strpos($html, 'Ouder artikel'),
            strpos($html, 'Nieuwer artikel'),
            'Het nieuwste artikel hoort bovenaan te staan.'
        );
    }

    public function test_the_detail_page_renders_the_sections_with_their_formatting(): void
    {
        $post = $this->article([], [
            ['title' => 'Eerste kop', 'body' => '<p>Tekst met <strong>vet</strong> en <em>schuin</em>.</p>'],
            ['title' => 'Tweede kop', 'body' => '<ul><li>Punt een</li><li>Punt twee</li></ul>'],
        ]);

        $this->get('/blog/' . $post->slug)
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee('Eerste kop')
            ->assertSee('Tweede kop')
            ->assertSee('<strong>vet</strong>', false)
            ->assertSee('<em>schuin</em>', false)
            ->assertSee('<li>Punt een</li>', false)
            ->assertSee('class="sm-prose', false);
    }

    public function test_a_draft_is_hidden_from_visitors_but_visible_to_an_admin(): void
    {
        $post = $this->article(['status' => PostStatus::Concept, 'published_at' => null]);

        $this->get('/blog/' . $post->slug)->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'artiest']))
            ->get('/blog/' . $post->slug)
            ->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/blog/' . $post->slug)
            ->assertOk()
            ->assertSee(__('blog.draft_notice'));
    }

    public function test_a_scheduled_post_shows_its_date_in_the_admin_preview(): void
    {
        $post = $this->article(['published_at' => now()->addWeek()]);

        $this->get('/blog/' . $post->slug)->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/blog/' . $post->slug)
            ->assertOk()
            ->assertSee($post->published_at->translatedFormat('j F Y'));
    }

    public function test_a_published_post_shows_no_preview_banner(): void
    {
        $post = $this->article();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/blog/' . $post->slug)
            ->assertOk()
            ->assertDontSee(__('blog.draft_notice'));
    }

    public function test_related_posts_exclude_the_article_itself(): void
    {
        $post = $this->article(['title' => 'Het hoofdartikel']);
        $this->article(['title' => 'Een ander artikel']);

        $this->get('/blog/' . $post->slug)
            ->assertOk()
            ->assertSee(__('blog.related_title'))
            ->assertSee('Een ander artikel');

        $solo = $this->article(['title' => 'Enige artikel', 'slug' => 'enige-artikel']);
        Post::whereKeyNot($solo->id)->delete();

        $this->get('/blog/' . $solo->slug)
            ->assertOk()
            ->assertDontSee(__('blog.related_title'));
    }

    public function test_the_table_of_contents_appears_from_three_headed_sections(): void
    {
        $two = $this->article(['title' => 'Twee koppen'], [
            ['title' => 'Kop een', 'body' => '<p>a</p>'],
            ['title' => 'Kop twee', 'body' => '<p>b</p>'],
        ]);

        $this->get('/blog/' . $two->slug)->assertOk()->assertDontSee(__('blog.toc_title'));

        $three = $this->article(['title' => 'Drie koppen'], [
            ['title' => 'Kop een', 'body' => '<p>a</p>'],
            ['title' => 'Kop twee', 'body' => '<p>b</p>'],
            ['title' => 'Kop drie', 'body' => '<p>c</p>'],
        ]);

        $this->get('/blog/' . $three->slug)
            ->assertOk()
            ->assertSee(__('blog.toc_title'))
            ->assertSee('href="#kop-drie"', false)
            ->assertSee('id="kop-drie"', false);
    }

    public function test_the_detail_page_carries_article_metadata(): void
    {
        $post = $this->article();

        $this->get('/blog/' . $post->slug)
            ->assertOk()
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('content="' . e($post->excerpt) . '"', false)
            ->assertSee('<link rel="canonical" href="' . url('/blog/' . $post->slug) . '">', false);
    }

    public function test_the_meta_description_falls_back_to_the_article_text(): void
    {
        $post = $this->article(['excerpt' => null], [
            ['title' => 'Kop', 'body' => '<p>De eerste zinnen van het artikel zelf.</p>'],
        ]);

        $this->assertSame('De eerste zinnen van het artikel zelf.', $post->metaDescription());

        $this->get('/blog/' . $post->slug)
            ->assertOk()
            ->assertSee('De eerste zinnen van het artikel zelf.');
    }

    public function test_the_sitemap_lists_the_blog_and_its_published_posts(): void
    {
        $live = $this->article(['title' => 'Live artikel']);
        $draft = $this->article(['title' => 'Concept artikel', 'status' => PostStatus::Concept, 'published_at' => null]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/blog'), false)
            ->assertSee(url('/blog/' . $live->slug), false)
            ->assertDontSee(url('/blog/' . $draft->slug), false);
    }

    public function test_the_blog_is_linked_from_the_site_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="' . url('/blog') . '"', false)
            ->assertSee(__('nav.blog'));
    }

    public function test_an_unknown_slug_returns_a_not_found(): void
    {
        $this->get('/blog/bestaat-niet')->assertNotFound();
    }
}
