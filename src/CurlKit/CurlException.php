<?php
namespace CurlKit;
use Exception;
/**
 * new CurlException(msg, code);
 */
class CurlException extends Exception {

    // http://curl.haxx.se/libcurl/c/libcurl-errors.html
    public function __construct($ch) {
        parent::__construct(curl_error($ch), curl_errno($ch));
        curl_close($ch); // close and free the resource
    }
}
