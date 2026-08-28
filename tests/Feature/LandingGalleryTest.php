<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingGalleryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_landing_page_renders()
    {
        $this->get('/')->assertStatus(200);
    }

    /** @test */
    public function the_gallery_offers_the_shop_video()
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('/images/shop_video.mp4', false)
            ->assertSee('/images/shop_video_poster.jpg', false)
            ->assertSee('galleryMainVideo', false)
            ->assertSee('Watch Our Shop Video');
    }

    /** @test */
    public function the_video_leads_the_gallery_and_the_photos_follow()
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        // The reel is slide 0; the four shop photos keep their order behind it.
        $this->assertMatchesRegularExpression(
            "/type: 'video'.*shop_gallery_1\.jpg.*shop_gallery_2\.jpg.*shop_gallery_3\.jpg.*shop_gallery_4\.jpg/s",
            $html
        );
    }

    /** @test */
    public function every_asset_the_landing_page_references_exists_on_disk()
    {
        $html = $this->get('/')->assertStatus(200)->getContent();

        preg_match_all('#/images/([A-Za-z0-9_.-]+)#', $html, $matches);
        $referenced = array_unique($matches[1]);

        $this->assertNotEmpty($referenced);

        foreach ($referenced as $asset) {
            $this->assertFileExists(
                public_path('images/' . $asset),
                'The landing page references images/' . $asset . ', which is not on disk.'
            );
        }
    }
}
