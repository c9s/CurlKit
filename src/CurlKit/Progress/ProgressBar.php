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

        $unit = 'B';
        if ($downloadSize > 1024) {
            $unit = 'KB';
            $downloadSize /= 1024;
            $downloaded /= 1024;
        }

        // print progress bar
        $percentage = ($downloaded > 0 ? (float) ($downloaded / $downloadSize) : 0.0 );
        $sharps = ceil(($this->terminalWidth - 15) * $percentage);

        # echo "\n" . $sharps. "\n";
        echo "\r" . 
            str_repeat( '#' , $sharps ) . 
            str_repeat( ' ' , $this->terminalWidth - $sharps ) . 
            sprintf( ' %4d/%4d %s %3d%%', $downloaded, $downloadSize, $unit, $percentage * 100 );

        if ( $downloadSize != 0 && $downloadSize === $downloaded ) {
            $this->done = true;
            echo "\n";
        }
    }
}

