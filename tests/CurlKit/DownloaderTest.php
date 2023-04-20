<?php

use CurlKit\CurlDownloader;
use PHPUnit\Framework\TestCase;

class DownloaderTest extends TestCase
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
        $response = $downloader->request('https://api.github.com');

        $data = json_decode($response, true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('https://api.github.com/user', $data['current_user_url']);
    }
}
