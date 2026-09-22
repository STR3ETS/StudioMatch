<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use App\Support\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBlogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Zo kies je de juiste opnamestudio',
            'excerpt' => 'Waar let je op als je voor het eerst een studio boekt?',
            'status' => PostStatus::Concept->value,
            'sections' => [
                ['title' => 'Begin bij je budget', 'body' => '<p>Bepaal eerst wat je <strong>maximaal</strong> wilt uitgeven.</p>'],
                ['title' => 'Kijk naar de apparatuur', 'body' => '<p>Vraag een lijst op van de <em>microfoons</em>.</p>'],
            ],
        ], $overrides);
    }

    public function test_only_admins_reach_the_blog_module(): void
    {
        $this->get('/dashboard/admin/blog')->assertRedirect();

        // De rolmiddleware stuurt een verkeerde rol terug naar het eigen dashboard.
        $this->actingAs(User::factory()->create(['role' => 'artiest']))
            ->get('/dashboard/admin/blog')
            ->assertRedirect(route('dashboard.artist'));

        $this->assertDatabaseCount('posts', 0);

        $this->actingAs($this->admin)->get('/dashboard/admin/blog')->assertOk();
    }

    public function test_admin_creates_a_post_with_sections_in_order(): void
    {
        $this->actingAs($this->admin)
            ->post('/dashboard/admin/blog', $this->payload())
            ->assertRedirect();

        $post = Post::firstOrFail();

        $this->assertSame('Zo kies je de juiste opnamestudio', $post->title);
        $this->assertSame('zo-kies-je-de-juiste-opnamestudio', $post->slug);
        $this->assertSame($this->admin->id, $post->user_id);
        $this->assertSame(PostStatus::Concept, $post->status);

        $this->assertCount(2, $post->sections);
        $this->assertSame('Begin bij je budget', $post->sections[0]->title);
        $this->assertSame(0, (int) $post->sections[0]->sort_order);
        $this->assertSame('Kijk naar de apparatuur', $post->sections[1]->title);
        $this->assertSame(1, (int) $post->sections[1]->sort_order);
    }

    public function test_the_edit_screen_shows_the_stored_sections(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload());

        $post = Post::firstOrFail();

        $this->actingAs($this->admin)
            ->get('/dashboard/admin/blog/' . $post->id)
            ->assertOk()
            ->assertSee('Begin bij je budget')
            ->assertSee('Kijk naar de apparatuur')
            ->assertSee('<strong>maximaal</strong>', false)
            ->assertSee('data-editor-command="bold"', false);
    }

    public function test_section_html_is_reduced_to_safe_formatting(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload([
            'sections' => [[
                'title' => 'Opmaak',
                'body' => '<p onclick="steal()" style="color:red">Hallo <b>vet</b> en <i>schuin</i></p>'
                    . '<script>alert(1)</script>'
                    . '<span style="font-size:80px">uit Word geplakt</span>'
                    . '<a href="javascript:alert(1)">foute link</a>',
            ]],
        ]))->assertRedirect();

        $body = Post::firstOrFail()->sections->first()->body;

        $this->assertStringContainsString('<strong>vet</strong>', $body);
        $this->assertStringContainsString('<em>schuin</em>', $body);
        $this->assertStringContainsString('uit Word geplakt', $body);
        $this->assertStringContainsString('foute link', $body);

        $this->assertStringNotContainsString('script', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('style', $body);
        $this->assertStringNotContainsString('javascript:', $body);
    }

    public function test_empty_sections_are_dropped(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload([
            'sections' => [
                ['title' => 'Blijft staan', 'body' => '<p>Met tekst.</p>'],
                ['title' => '', 'body' => '<p><br></p>'],
                ['title' => '', 'body' => ''],
            ],
        ]))->assertRedirect();

        $this->assertCount(1, Post::firstOrFail()->sections);
    }

    public function test_updating_keeps_existing_sections_and_removes_the_rest(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload());

        $post = Post::with('sections')->firstOrFail();
        [$first, $second] = [$post->sections[0], $post->sections[1]];

        // Volgorde omgedraaid, de eerste hernoemd en de tweede weggelaten.
        $this->actingAs($this->admin)->put('/dashboard/admin/blog/' . $post->id, $this->payload([
            'sections' => [
                ['id' => $first->id, 'title' => 'Nieuwe kop', 'body' => '<p>Aangepast.</p>'],
                ['title' => 'Verse alinea', 'body' => '<p>Net toegevoegd.</p>'],
            ],
        ]))->assertRedirect();

        $sections = $post->fresh()->sections;

        $this->assertCount(2, $sections);
        $this->assertSame($first->id, $sections[0]->id);
        $this->assertSame('Nieuwe kop', $sections[0]->title);
        $this->assertSame('Verse alinea', $sections[1]->title);
        $this->assertDatabaseMissing('post_sections', ['id' => $second->id]);
    }

    public function test_publishing_without_a_date_goes_live_right_away(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload([
            'status' => PostStatus::Gepubliceerd->value,
        ]))->assertRedirect();

        $post = Post::firstOrFail();

        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->isPublished());
        $this->assertFalse($post->isScheduled());
    }

    public function test_a_future_date_schedules_the_post_instead_of_publishing_it(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload([
            'status' => PostStatus::Gepubliceerd->value,
            'published_at' => now()->addWeek()->format('Y-m-d\TH:i'),
        ]))->assertRedirect();

        $post = Post::firstOrFail();

        $this->assertTrue($post->isScheduled());
        $this->assertFalse($post->isPublished());
        $this->assertSame(0, Post::published()->count());
    }

    public function test_slugs_stay_unique_and_can_be_set_by_hand(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload());
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload());

        $this->assertSame(
            ['zo-kies-je-de-juiste-opnamestudio', 'zo-kies-je-de-juiste-opnamestudio-2'],
            Post::orderBy('id')->pluck('slug')->all()
        );

        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload([
            'slug' => 'Niet Toegestaan',
        ]))->assertSessionHasErrors('slug');
    }

    public function test_saving_an_existing_post_does_not_bump_its_own_slug(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload());
        $post = Post::firstOrFail();

        $this->actingAs($this->admin)
            ->put('/dashboard/admin/blog/' . $post->id, $this->payload(['slug' => $post->slug]))
            ->assertRedirect();

        $this->assertSame('zo-kies-je-de-juiste-opnamestudio', $post->fresh()->slug);
    }

    public function test_cover_photo_is_stored_and_can_be_removed_again(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload([
            'cover' => UploadedFile::fake()->image('omslag.jpg'),
        ]))->assertRedirect();

        $post = Post::firstOrFail();
        $this->assertNotNull($post->cover_path);
        Storage::disk('public')->assertExists($post->cover_path);

        $path = $post->cover_path;

        $this->actingAs($this->admin)
            ->put('/dashboard/admin/blog/' . $post->id, $this->payload(['remove_cover' => '1']))
            ->assertRedirect();

        $this->assertNull($post->fresh()->cover_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_a_post_takes_its_sections_and_cover_with_it(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post('/dashboard/admin/blog', $this->payload([
            'cover' => UploadedFile::fake()->image('omslag.jpg'),
        ]));

        $post = Post::firstOrFail();
        $path = $post->cover_path;

        $this->actingAs($this->admin)
            ->delete('/dashboard/admin/blog/' . $post->id)
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('post_sections', 0);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_the_edit_screen_replays_unsaved_work_after_a_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->post('/dashboard/admin/blog', $this->payload(['title' => '']))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('posts', 0);

        // De oude invoer komt ongefilterd terug en mag niet als script in de editor belanden.
        $this->actingAs($this->admin)
            ->withSession(['_old_input' => [
                'title' => 'Terug',
                'sections' => [['title' => 'Kop', 'body' => '<p>Tekst</p><script>alert(1)</script>']],
            ]])
            ->get('/dashboard/admin/blog/nieuw')
            ->assertOk()
            ->assertSee('<p>Tekst</p>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_published_scope_only_returns_live_posts(): void
    {
        $live = Post::create(['title' => 'Live', 'status' => PostStatus::Gepubliceerd, 'published_at' => now()->subDay()]);
        Post::create(['title' => 'Concept', 'status' => PostStatus::Concept]);
        Post::create(['title' => 'Ingepland', 'status' => PostStatus::Gepubliceerd, 'published_at' => now()->addDay()]);

        $this->assertSame([$live->id], Post::published()->pluck('id')->all());
    }

    public function test_rich_text_helper_keeps_safe_links_and_drops_the_rest(): void
    {
        $this->assertSame(
            '<a href="/studios">intern</a>',
            RichText::clean('<a href="/studios" onclick="x()">intern</a>')
        );

        $this->assertStringContainsString('rel="noopener nofollow"', RichText::clean('<a href="https://voorbeeld.nl">extern</a>'));
        $this->assertSame('klik', RichText::clean('<a href="javascript:alert(1)">klik</a>'));
        $this->assertSame('', RichText::clean('<p>&nbsp;</p><p><br></p>'));
    }
}
