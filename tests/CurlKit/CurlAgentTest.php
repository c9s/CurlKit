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

    public function testingProxy() {
        skip('skip proxy testing');
        $agent = new CurlKit\CurlAgent;
        $agent->setProxy('106.187.96.49:3128');
        $response = $agent->get('https://stackoverflow.com/questions/11297320/using-a-try-catch-with-curl-in-php');
        ok($response);
        ok($response->body);
        ok($response->headers);
    }
}

