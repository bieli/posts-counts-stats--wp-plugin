<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Post_Count_Stats;

if (!function_exists('esc_html')) {
    function esc_html($text) { return $text; }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return $text; }
}
if (!function_exists('admin_url')) {
    function admin_url($path) { return 'https://example.com' . $path; }
}
if (!function_exists('date_i18n')) {
    function date_i18n($format, $timestamp) { return 'January'; }
}

class PostsCountsStatsTest extends TestCase {
    
    protected function setUp(): void {
        parent::setUp();
        
        if (!defined('ABSPATH')) {
            define('ABSPATH', dirname(__DIR__, 2) . '/');
        }

        require_once ABSPATH . 'posts-counts-stats.php';

        $wpdbMock = $this->getMockBuilder(\stdClass::class)
            ->allowMockingUnknownTypes()
            ->addMethods(['get_results'])
            ->getMock();

        $wpdbMock->posts = 'wp_posts';

        $GLOBALS['wpdb'] = $wpdbMock;
    }

    protected function tearDown(): void {
        unset($GLOBALS['wpdb']);
        parent::tearDown();
    }

    public function testRenderSettingsPageYearlyStats() {
        $GLOBALS['wpdb']->method('get_results')
            ->willReturn([
                (object) ['year' => 2023, 'month' => 5, 'post_count' => 10],
                (object) ['year' => 2024, 'month' => 6, 'post_count' => 20]
            ]);

        ob_start();
        $plugin = new Post_Count_Stats();
        $plugin->render_settings_page();
        $output = ob_get_clean();

        $this->assertStringContainsString('2023', $output);
        $this->assertStringContainsString('10', $output);
        $this->assertStringContainsString('2024', $output);
        $this->assertStringContainsString('20', $output);
    }
}
