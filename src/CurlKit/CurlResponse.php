<?php
namespace CurlKit;
use ArrayAccess;
use Exception;

class CurlResponse { 

    public $rawHeaderBody;

    public $headers = array();

    public $body;

    public function __construct($body, $rawHeaderBody = null) {
        $this->body = $body;
        if ( $rawHeaderBody ) {
            $this->rawHeaderBody = $rawHeaderBody;
            $this->headers = $this->parseHttpHeader($rawHeaderBody);
        }
    }

    /**
     * Create response object from the raw response 
     *  The raw response includes HTTP header and body
     */
    public static function createFromRawResponse($ch, $rawResponse) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeader  = substr($rawResponse, 0, $headerSize);
        $body       = substr($rawResponse, $headerSize);
        return new self($body, $rawHeader);
    }

    public function hasHeader($field) {
        return isset($this->headers[$field]);
    }

    public function getHeader($field) {
        if ( isset($this->headers[$field]) ) {
            return $this->headers[$field];
        }
    }

    public function parseHttpHeader($rawHeaderBody) {
        $headers = array();
        $lines   = explode("\r\n", $rawHeaderBody);
        $status  = array_shift($lines);
        foreach( $lines as $line ) {
            if ( strpos($line,':') !== false ) {
                list($key, $value) = explode(':', $line);
                $headers[strtolower($key)] = trim($value);
            }
        }
        return $headers;
    }

    public function decodeBody() {
        if ($contentType = $this->getHeader('content-type') ) {
            // Content-Type: application/json; charset=utf-8
            if ( preg_match('#(?:application|text)/json#', $contentType) ) {
                // over-write the text body with our decoded json object
                return json_decode($this->body);
            }
        }
        return $this->body;
    }

}
