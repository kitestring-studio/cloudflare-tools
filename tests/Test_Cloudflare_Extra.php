<?php

use KitestringStudio\CloudflareTools\Cloudflare_Extra;

class Test_Cloudflare_Extra extends \WP_UnitTestCase {
    public function test_filter_urls() {
        // Create an instance of the class
        $cloudflare_extra = new Cloudflare_Extra();

        // Example input
        $urls = ['https://example.com/page1'];
        $postId = 42;

        // Expected output
        $expected_urls = [
            'https://example.com/page1',
            'https://example.com/page2', // From posts with "always_purge"
            'https://example.com/extra'  // From additional URLs
        ];

        // Mock WordPress functions as needed (e.g., get_option, get_permalink)

        // Call the method
        $result = $cloudflare_extra->filter_urls($urls, $postId);

        // Assert the result
        $this->assertEquals($expected_urls, $result);
    }

    // Additional test methods for private functions can be added
    // using reflection to make them accessible
}
