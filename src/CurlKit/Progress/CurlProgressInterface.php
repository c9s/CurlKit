<?php
namespace CurlKit\Progress;

interface CurlProgressInterface {
    public function curlCallback($ch, $downloadSize, $downloaded, $uploadSize, $uploaded);
}


