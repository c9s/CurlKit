<?php
namespace CurlKit\Progress;
use Exception;
use CurlKit\Progress\CurlProgressInterface;

class ProgressBar
    implements CurlProgressInterface
{

    public $done = false;

    public $terminalWidth = 78;

    public function curlCallback($ch, $downloadSize, $downloaded, $uploadSize, $uploaded)
    {
        if ($this->done) {
            return;
        }

        // print progress bar
        $percentage = ($downloaded > 0 ? (float) ($downloaded / $downloadSize) : 0.0 );
        $sharps = ceil($this->terminalWidth * $percentage);

        # echo "\n" . $sharps. "\n";
        echo "\r" . 
            str_repeat( '#' , $sharps ) . 
            str_repeat( ' ' , $this->terminalWidth - $sharps ) . 
            sprintf( ' %4d B %5d%%' , $downloaded , $percentage * 100 );

        if ( $downloadSize != 0 && $downloadSize === $downloaded ) {
            $this->done = true;
            echo "\n";
        }
    }
}

