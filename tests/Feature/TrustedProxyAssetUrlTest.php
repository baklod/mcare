<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyAssetUrlTest extends TestCase
{
    public function test_loopback_ngrok_proxy_generates_https_asset_urls(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Host' => 'mcare-demo.ngrok-free.app',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('https://mcare-demo.ngrok-free.app/build/assets/', false)
            ->assertDontSee('http://mcare-demo.ngrok-free.app/build/assets/', false);
    }
}
