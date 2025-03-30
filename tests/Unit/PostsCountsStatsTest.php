<?php
use PHPUnit\Framework\TestCase;

class PostCountStatsTest extends TestCase {
    private $wpdb;

    protected function setUp(): void {
        // Mock $wpdb
        $this->wpdb = $this->getMockBuilder(stdClass::class)
            ->setMethods(['get_results'])
            ->getMock();
    }

    public function testRenderSettingsPageYearlyStats() {
        // Mock data for yearly stats
        $this->wpdb->method('get_results')
            ->willReturn([
                (object) ['year' => 2023, 'post_count' => 10],
                (object) ['year' => 2024, 'post_count' => 20]
            ]);

        // Call the method and check output
        ob_start();
        $plugin = new Post_Count_Stats();
        $plugin->render_settings_page();
        $output = ob_get_clean();

        // Assert HTML contains expected values
        $this->assertStringContainsString('Year: 2023', $output);
        $this->assertStringContainsString('10', $output);
        $this->assertStringContainsString('Year: 2024', $output);
        $this->assertStringContainsString('20', $output);
    }
}
?>
