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

    /**
     * A stray apostrophe in a caption once ended a JS string early and threw a
     * SyntaxError, which killed every function in that script block — the whole
     * gallery, video and photos alike. Parsing the inline scripts catches that
     * class of break, which no amount of HTML assertion would.
     *
     * @test
     */
    public function the_inline_scripts_on_the_landing_page_parse()
    {
        exec('node --version 2>&1', $probe, $probeCode);
        if ($probeCode !== 0) {
            $this->markTestSkipped('node is not available to parse-check the scripts.');
        }

        $html = $this->get('/')->assertStatus(200)->getContent();

        preg_match_all('#<script(?![^>]*src=)[^>]*>(.*?)</script>#si', $html, $matches);
        $scripts = array_filter($matches[1], fn ($js) => trim($js) !== '');

        $this->assertNotEmpty($scripts, 'No inline scripts were found to check.');

        foreach ($scripts as $index => $js) {
            $file = tempnam(sys_get_temp_dir(), 'jscheck') . '.js';
            file_put_contents($file, $js);

            $output = [];
            exec('node --check ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);
            @unlink($file);

            $this->assertSame(
                0,
                $exitCode,
                'Inline script #' . $index . ' does not parse:' . PHP_EOL . implode(PHP_EOL, $output)
            );
        }
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
