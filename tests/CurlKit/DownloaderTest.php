<?php

use CurlKit\CurlDownloader;

class DownloaderTest extends PHPUnit_Framework_TestCase
{
    public function testDownloadDefault()
    {
        $this->assertDownload(new CurlDownloader());
    }

    public function testDownloadViaProxy()
    {
        $proxy = getenv('http_proxy');

        if ($proxy === false) {
            skip('Set an HTTP proxy using the http_proxy environment variable');
        }

        $downloader = new CurlDownloader();
        $downloader->setProxy($proxy);

        $this->assertDownload($downloader);
    }

    private function assertDownload(CurlDownloader $downloader)
    {
        $response = $downloader->request('https://httpbin.org/get');

        $data = json_decode($response, true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('https://httpbin.org/get', $data['url']);
    }
}
