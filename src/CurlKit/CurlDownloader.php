<?php
namespace CurlKit;
use CurlKit\Progress\CurlProgressInterface;
use CurlKit\CurlException;

/**
 * $downloader = new CurlKit/CurlDownloader;
 * $downloader->setBasicCredential( 'user', 'password' );
 * $downloader->setProxy( 'user', 'password' );
 * $downloader->setProgress( new StarProgress );
 *
 * // Get request
 * $downloader->request( 'http://.....' , array(
 *      'param' => 1,
 *      'param' => 2,
 * ));
 *
 * // Post request
 * $downloader->post( 'http://....' , array( 
 * $downloader->requestXml( 'http://.....' , array( ... ) );
 *
 * ));
 */
use Exception;

class CurlDownloader 
{

    public $options = array( 
        CURLOPT_HEADER => 1,
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_NOPROGRESS => true,
        CURLOPT_FAILONERROR => 1,
    );

    public $refreshConnect = 1;

    public $followLocation = 1;

    public $bufferSize = 512;

    public $connectionTimeout = 10;

    public $timeout = 36000;

    public $progress;

    public $proxy;

    public $proxyAuth;

    public function __construct($options = array() )
    {
        if (isset($options['progress'])) {
            $this->setProgressHandler($options['progress']);
        }
    }

    public function createCurlResource( $extra = array() ) 
    {
        $ch = curl_init(); 
        curl_setopt_array($ch, (
            $this->options 
                + array( 
                    CURLOPT_FRESH_CONNECT => $this->refreshConnect,
                    // CURLOPT_FOLLOWLOCATION => $this->followLocation,

                    CURLOPT_BUFFERSIZE => $this->bufferSize,

                    // connection timeout
                    CURLOPT_CONNECTTIMEOUT => $this->connectionTimeout,

                    // max function call timeout
                    CURLOPT_TIMEOUT => $this->timeout,
                ) 
                + $extra
        )); 

        if ($this->proxy) {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            if ( $this->proxyAuth ) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyAuth);
            }
        }
        return $ch;
    }

    public function setBufferSize($bytes) {
        $this->bufferSize = $bytes;
    }

    public function setTimeout($seconds)
    {
        $this->timeout = $seconds;
    }

    public function setConnectionTimeout( $seconds )
    {
        $this->connectionTimeout = $seconds;
    }

    /**
     * Set progress handler
     *
     * @param $callback
     */
    public function setProgressHandler(CurlProgressInterface $handler)
    {
        $this->progress = $handler;
        $this->options[ CURLOPT_NOPROGRESS ] = false;

        // Setup progress handler
        if (version_compare(phpversion(),"5.5.0") >= 0) {
            $this->options[ CURLOPT_PROGRESSFUNCTION ] = array($this,'updateProgress5');
        } else {
            $this->options[ CURLOPT_PROGRESSFUNCTION ] = array($this,'updateProgress4');
        }
    }

    public function updateProgress4($downloaded, $totalDownload, $upload, $totalUpload) {
        $this->progress->curlCallback(NULL, $downloaded, $totalDownload, $upload, $totalUpload);
    }

    public function updateProgress5($ch, $downloaded, $totalDownload, $upload, $totalUpload) {
        $this->progress->curlCallback($ch, $downloaded, $totalDownload, $upload, $totalUpload);
    }

    public function getProgressHandler()
    {
        return $this->progress;
    }

    public function fetch($url)
    {
        return $this->request( $url );
    }

    public function request($url, $params = array() , $options = array() ) 
    {
        if ($this->progress) {
            $this->progress->done = false;
        }
        $options[ CURLOPT_URL ] = $url;
        $ch = $this->createCurlResource($options);
        $data = curl_exec($ch);
        if (!$data) {
            throw new CurlException($ch, $url . ":" . curl_error($ch) );
        }

        // We don't enable CURLOPT_FOLLOWLOCATION because
        // the progress bar does not work after the 1st redirect..
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $headers = '';
        $body = '';

        // When using HTTP TUNNEL, there is an extra response line before the 
        // original response line, we need to separate them if it matches "Connection established"
        if (preg_match('#HTTP/1.1 200 Connection established#i', $data)) {
            list($proxyResponseLine, $headers, $body) = explode("\r\n\r\n", $data, 3);
        } else {
            list($headers, $body) = explode("\r\n\r\n", $data, 2);
        }
        if ($code == 301 || $code == 302) {
            if (preg_match('/Location:\s*(\S+)/', $headers, $matches)) {
                $newurl = trim(array_pop($matches));
                curl_close($ch);
                echo "Redirecting to $newurl\n";
                return $this->request($newurl, $params, $options);
            } else {
                throw new CurlException($ch, "The Location header can not be found: " . $headers);
            }
        }
        curl_close($ch); 
        return $body;
    }

    /**
     * Set Proxy
     *
     * @param string $proxy this parameter is a string in 127.0.0.1:8888 format.
     */
    public function setProxy($proxy) {
        $this->proxy = $proxy;
    }

    public function setProxyAuth($auth) {
        $this->proxyAuth = $auth;
    }

    public function redirectExec($ch, &$redirects, $curlopt_header = false) {
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 301 || $http_code == 302) {
            list($header) = explode("\r\n\r\n", $data, 2);
            $matches = array();
            preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
            $url = trim(array_pop($matches));
            $url_parsed = parse_url($url);
            if (isset($url_parsed)) {
                $ch = $this->createCurlResource(array( CURLOPT_URL => $url));
                $redirects++;
                return $this->redirectExec($ch, $redirects);
            }
        }
        if ($curlopt_header) {
            return $data;
        } else {
            list($headers,$body) = explode("\r\n\r\n", $data, 2);
            return $body;
        }
    }

    // curl_setopt($s,CURLOPT_MAXREDIRS,$this->_maxRedirects); 
    // curl_setopt($s,CURLOPT_FOLLOWLOCATION,$this->_followlocation); 
    // curl_setopt($tuCurl, CURLOPT_POST, 1); 
    // curl_setopt($tuCurl, CURLOPT_POSTFIELDS, $data); 
    
    // curl_setopt($s,CURLOPT_COOKIEJAR,$this->_cookieFileLocation); 
    // curl_setopt($s,CURLOPT_COOKIEFILE,$this->_cookieFileLocation); 


    // basic auth
    // curl_setopt($s, CURLOPT_USERPWD, $this->auth_name.':'.$this->auth_pass); 
    //
    // get info
    // $this->_status = curl_getinfo($s,CURLINFO_HTTP_CODE); 
    
}

