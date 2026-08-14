<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineOverlayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_connection_probe_answers_with_no_content()
    {
        // Symfony re-sorts the directives, so assert on the parts that matter
        // rather than the literal header string.
        $response = $this->get('/connection-check')->assertNoContent();

        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    /** @test */
    public function the_connection_probe_is_reachable_without_a_session()
    {
        // The route opts out of StartSession so polling cannot contend for the
        // file-session lock. A session cookie coming back would mean it crept in.
        $response = $this->get('/connection-check');

        $this->assertNull(
            $response->getCookie(config('session.cookie')),
            'The probe should not start a session.'
        );
    }

    /** @test */
    public function the_blocker_is_rendered_on_a_guest_page()
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('id="offlineOverlay"', false)
            ->assertSee('No Internet Connection');
    }

    /** @test */
    public function the_blocker_is_rendered_after_the_chatbot_so_it_stacks_on_top()
    {
        $html = $this->get('/login')->assertStatus(200)->getContent();

        $chatbot = strpos($html, 'merasChatbotWrap');
        $overlay = strpos($html, 'offlineOverlay');

        $this->assertNotFalse($chatbot, 'Chatbot widget was not rendered.');
        $this->assertNotFalse($overlay, 'Offline blocker was not rendered.');
        $this->assertLessThan(
            $overlay,
            $chatbot,
            'The blocker must come after the chatbot in the DOM; they share a z-index.'
        );
    }
}
