<?php
namespace CurlKit\Progress;

interface CurlProgressInterface {
    public function curlCallback($downloadSize, $downloaded, $uploadSize, $uploaded);
}


