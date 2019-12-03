<?php

class CurlAgentTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException CurlKit\CurlException
     */
    public function testError() {
        $agent = new CurlKit\CurlAgent;
        ok($agent);
        $response = $agent->get('http://does.not.exist');
    }

    public function testGet()
    {
        $agent = new CurlKit\CurlAgent;
        ok($agent);

        $response = $agent->get('https://stackoverflow.com/questions/11297320/using-a-try-catch-with-curl-in-php');
        ok($response);
        ok($response->body);
        ok($response->headers);
        ok(is_array($response->headers));
    }

    public function testHead()
    {
        $agent = new CurlKit\CurlAgent;
        ok($agent);

        $response = $agent->head('http://httpbin.org/');
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
        $response = $agent->get('https://stackoverflow.com/questions/11297320/using-a-try-catch-with-curl-in-php');
        ok($response);
        ok($response->body);
        ok($response->headers);
    }
}

