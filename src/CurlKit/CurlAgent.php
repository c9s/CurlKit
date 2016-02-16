<?php
namespace CurlKit;
use ArrayAccess;
use Exception;
use CurlKit\CurlException;
use CurlKit\CurlRequest;
use CurlKit\CurlResponse;


define('CRLF', "\r\n");

class CurlAgent implements ArrayAccess {

    public $throwException = true;

    public $cookieFile;

    public $sslVerifyhost = 0;

    public $sslVerifypeer = 0;

    public $followLocation = 1;

    public $receiveHeader = true;

    public $userAgent;

    public $proxy;

    public $proxyAuth;

    public $connectionTimeout = 30;

    public $timeout = 0;

    public $failOnError = true;

    protected $_curlOptions = array();

    public function __construct() {
        $this->cookieFile = tempnam("/tmp", str_replace('\\','_',get_class($this)) . mt_rand());
    }

    /**
     * Set Proxy
     *
     * @param string $proxy this parameter is a string in 127.0.0.1:8888 format.
     */
    public function setProxy($proxy, $auth = null) {
        $this->proxy = $proxy;
        if ($auth) {
            $this->proxyAuth = $auth;
        }
    }

    public function setProxyAuth($auth) {
        $this->proxyAuth = $auth;
    }

    public function setTimeout($secs) {
        $this->timeout = $secs;
    }

    public function setConnectionTimeout($secs) {
        $this->connectionTimeout = $secs;
    }

    protected function _handleCurlError($ch) {
        if ( $this->throwException ) {
            // the CurlException close the curl response automatically
            throw new CurlException($ch);
        }
        return FALSE;
    }

    protected function _handleCurlResponse($ch, $rawResponse) {
        $ret = null;
        if ($rawResponse) {
            if ( $this->receiveHeader ) {
                $ret = CurlResponse::createFromRawResponse($ch, $rawResponse);
            } else {
                $ret = new CurlResponse($rawResponse);
            }

            if (getenv('DEBUG_RESPONSE')) {
                echo "RESPONSE:\n";
                print_r($ret->decodeBody());
            }

        } else {
            $ret = $this->_handleCurlError($ch);
        }



        curl_close($ch);
        return $ret;
    }



    protected function _createCurlInstance() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_COOKIEJAR,  $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->sslVerifyhost);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->sslVerifypeer);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->followLocation);


        curl_setopt($ch, CURLOPT_FAILONERROR, $this->failOnError);

        // curl_setopt($this->curl, CURLOPT_WRITEFUNCTION, array($this, "curl_handler_recv")); 

        if ($this->connectionTimeout)  {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout );
        }
        if ($this->timeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        }

        curl_setopt($ch, CURLINFO_HEADER_OUT, true );

        if ( $this->proxy ) {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            if ( $this->proxyAuth ) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyAuth);
            }
        }

        if ( $this->userAgent ) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent );
        }
        if ( $this->receiveHeader ) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }

        foreach( $this->_curlOptions as $k => $v) {
            curl_setopt($ch, $k, $v);
        }

        return $ch;
    }

    protected function _readResponseBody($responseBody) {
        return explode( CRLF . CRLF, $responseBody);
    }

    protected function _separateResponse($ch, $rawResponse) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeader  = substr($rawResponse, 0, $headerSize);
        $body       = substr($rawResponse, $headerSize);
        return new CurlResponse($body, $rawHeader);
    }

    public function get($url, $fields = array(), $headers = array() ) {
        $ch = $this->_createCurlInstance();
        $request = new CurlRequest($url, 'GET', $fields, $headers);
        $request->applyCurlResource($ch);
        if ( getenv('DEBUG_REQUEST') ) {
            echo "REQUEST:\n";
            print_r($fields);
        }
        return $this->sendRequest($request);
    }

    public function post($url, $fields = array() , $headers = array()) {
        $ch = $this->_createCurlInstance();
        $request = new CurlRequest($url, 'POST', $fields, $headers);
        $request->applyCurlResource($ch);
        if ( getenv('DEBUG_REQUEST') ) {
            echo "REQUEST: $url\n";
            print_r($fields);
        }
        return $this->sendRequest($request);
    }

    public function head($url, $fields = array() , $headers = array() ) {
        $ch = $this->_createCurlInstance();
        $request = new CurlRequest($url, 'HEAD', $fields, $headers);
        $request->applyCurlResource($ch);
        if ( getenv('DEBUG_REQUEST') ) {
            echo "REQUEST:\n";
            print_r($fields);
        }
        return $this->sendRequest($request);
    }

    public function sendRequest(CurlRequest $request) {
        $rawResponse = $request->send();
        $response = $this->_handleCurlResponse($request->curlResource, $rawResponse);
        $request->setResponse($response);
        return $response;
    }


    public function __destruct() {
        if( file_exists($this->cookieFile) ) {
            unlink($this->cookieFile);
        }
    }


    
    public function offsetSet($name,$value)
    {
        $this->_curlOptions[ $name ] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->_curlOptions[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->_curlOptions[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->_curlOptions[$name]);
    }

}

