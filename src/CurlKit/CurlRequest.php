<?php
namespace CurlKit;

class CurlRequest
{

    /**
     * @var string request url
     */
    public $url;

    /**
     * @var string the default request method
     */
    public $method = 'GET';

    public $parameters = array();


    /**
     * @var array header strings "field name" => "field value"
     *
     *     'Content-Type' => '...'
     *     'Accept' => 'text/xml'
     */
    public $headers = array();

    public $response;


    /**
     * @var resource curl resource
     */
    public $curlResource;

    public function __construct($url, $method = 'GET', $parameters = array(), $headers = array() ) {
        $this->url = $url;
        $this->method = $method;
        $this->parameters = $parameters;
        $this->headers = $headers;
    }

    public function setHeaders(array $headers) {
        $this->headers = $headers;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function setResponse(CurlResponse $resp) {
        $this->response = $resp;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getEncodedParameters() {
        return http_build_query($this->parameters);
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function getParameterCount() {
        return count($this->parameters);
    }
    

    public function applyCurlResource($ch) {
        if ( $this->method == 'GET' ) {
            $url = $this->url;
            if ( $query = $this->getEncodedParameters() ) {
                $url .= '?' . $query;
            }
            curl_setopt($ch, CURLOPT_URL, $url);
        } elseif ( $this->method == 'POST' ) {
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_POST, $this->getParameterCount());
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getEncodedParameters() );
        } elseif ( $this->method === 'HEAD' ) {
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->url);
        }

        // if header is set
        if ( ! empty($this->headers) ) {
            $headerLines = array();
            foreach( $this->headers as $key => $value ) {
                $headerLines[] = $key . ':' . $value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        }
        $this->curlResource = $ch;
    }

    public function send() {
        if ( ! $this->curlResource ) {
            throw new RuntimeException('');
        }
        return curl_exec($this->curlResource);
    }

    protected function _encodeFields(array & $fields) {
        $fieldsString = '';
        foreach( $fields as $key => $value ) { 
            $fieldsString .= $key.'='. urlencode($value) .'&'; 
        }
        return rtrim($fieldsString, '&');
    }


}

