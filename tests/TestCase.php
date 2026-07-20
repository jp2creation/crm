<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function crmPngDataUrl(int $width = 2, int $height = 2): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 149, 0, 46);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode((string) $contents);
    }
}
