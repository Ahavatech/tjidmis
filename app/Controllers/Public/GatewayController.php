<?php
namespace App\Controllers\Public;
use App\Core\Settings;
use App\Services\FeedService;
class GatewayController {
    public function plugin(string $name = '', string $fmt = ''): void {
        if ($name !== 'WebFeedGatewayPlugin' || !in_array($fmt, ['atom', 'rss2', 'rss'], true)) { http_response_code(404); echo 'Not found'; return; }
        [$type, $body] = FeedService::render((int)Settings::journal()['id'], $fmt);
        header('Content-Type: ' . $type . '; charset=utf-8'); echo $body;
    }
}
