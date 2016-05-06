<?php
namespace CurlKit;
use Exception;
/**
 * new CurlException(msg, code);
 */
class CurlException extends Exception
{
    protected $url;

    // http://curl.haxx.se/libcurl/c/libcurl-errors.html
    public function __construct($ch, $url = null)
    {
        parent::__construct(curl_error($ch) . ' at [' . $url . ']', curl_errno($ch));
        curl_close($ch); // close and free the resource
    }
}
