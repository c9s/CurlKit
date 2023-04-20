<?php

use PHPUnit\Framework\TestCase;

class CurlAgentTest extends TestCase
{

    public function testError() {
        $agent = new CurlKit\CurlAgent;
        ok($agent);
        $this->expectException('CurlKit\\CurlException');
        $response = $agent->get('http://does.not.exist');
    }

    public function testGet()
    {
        $agent = new CurlKit\CurlAgent;
        ok($agent);

        $response = $agent->get('https://github.com');
        ok($response);
        ok($response->body);
        ok($response->headers);
        ok(is_array($response->headers));
    }

    public function testHead()
    {
        $agent = new CurlKit\CurlAgent;
        ok($agent);

        $response = $agent->head('https://github.com');
        ok($response);
        is('', $response->body);
        ok($response->headers);
        ok(is_array($response->headers));
    }

    public function testingProxy() {
        $proxy = getenv('http_proxy');

        if ($proxy === false) {
            skip('Set an HTTP proxy using the http_proxy environment variable');
        }

        $agent = new CurlKit\CurlAgent;
        $agent->setProxy($proxy);
        $response = $agent->get('https://github.com');
        ok($response);
        ok($response->body);
        ok($response->headers);
    }
}

