<?php

    // Define custom exception to allow explicit catching of exceptions from the
    // RoundedImage class.
    class ImageException extends Exception {};

    // The class containing the maths and image manipulation necessary for
    // accurately curving and anti-aliasing rectangular images
    class RoundedImage
    {
        // Define a simple way to distinguish which corner should be acted upon
        const TOP_LEFT     = 1;
        const TOP_RIGHT    = 2;
        const BOTTOM_LEFT  = 3;
        const BOTTOM_RIGHT = 4;

        // Set a few class variables including the brush used to erase unwanted parts
        // of the image 
        private $b = 0x7F000000;
        private $c = null;
        private $h = 0;
        private $w = 0;

        // Construct instance, load in the image, $src, abort if any problems are
        // encountered; doesn't exist, not an image, unsupported format etc
        public function __construct($src)
        {
            // Check $src exists

            if ( !file_exists($src) )
                throw new ImageException("$src does not exist");

            // Query image ($src) for dimensions, type etc. Throw exception if unable to
            // obtain information (i.e. the file is not an image)

            $info = @getimagesize($src);
            if ( empty($info) )
                throw new ImageException("$src is not a valid image file");

            // Store the width and height values in the class scope

            $this->w = $info[0];
            $this->h = $info[1];
            $mime = $info['mime'];

            // Attempt to determine the correct function to use when loading the image.
            // Use the latter half of the MIME-type (jpeg, gif, png etc) to construct the
            // name of the imagecreate function, then check that it exists. If everything
            // checks out, load the image

            if ( preg_match('/^image\/(.*)/', $mime, $type) )
            {
                $f = "imagecreatefrom" . $type[1];
                if ( function_exists($f) )
                    $i = $f($src);
                else
                    throw new ImageException("The $mime format is not currently supported by the imagecreate family of functions");
            }
            else
                throw new ImageException("Unable to detect the MIME type of $src");

            // Set up a truecolor canvas to give access to a large number of RGBA colours

            if ( !( $this->c = @imagecreatetruecolor($this->w, $this->h) ) )
                throw new ImageException("Cannot initialize new GD image stream.");
            imagealphablending($this->c, false);
            imagesavealpha($this->c, true);

            // Copy the image onto the canvas

            imagecopy($this->c, $i, 0, 0, 0, 0, $this->w, $this->h);

            // Finally, release the image

            imagedestroy($i);
        }

        // Ensure all resources are disposed of cleanly
        public function __destruct()
        {
            // Release the canvas

            imagedestroy($this->c);
        }

        // A nice simple function to allow easy rounding of all four corners at once
        public function roundCorners($r1, $r2 = null, $r3 = null, $r4 = null)
        {
            // If not set, propogate default values.

            if ( !$r1 )
                $r1 = 0;
            if ( is_null($r2) )
                $r2 = $r1;
            if ( is_null($r3) )
                $r3 = $r1;
            if ( is_null($r4) )
                $r4 = $r2;

            // Check each radius so we don't call the round function unnecessarily

            if ( $r1 > 0 )
                $this->round(              0,              0, $r1,     RoundedImage::TOP_LEFT);
            if ( $r2 > 0 )
                $this->round( $this->w - $r2,              0, $r2,    RoundedImage::TOP_RIGHT);
            if ( $r3 > 0 )
                $this->round( $this->w - $r3, $this->h - $r3, $r3, RoundedImage::BOTTOM_RIGHT);
            if ( $r4 > 0 )
                $this->round(              0, $this->h - $r4, $r4,  RoundedImage::BOTTOM_LEFT);
        }

        // Encodes the canvas to png24 and returns the data as a string
        public function getImageData()
        {
            ob_start();
            imagepng($this->c);
            $img = ob_get_clean();
            return $img;
        }

        // The real workhorse of the class, this is where the curvy magic happens
        private function round($xo, $yo, $r, $l)
        {
            // Set the default x and y-axis modifiers ($xm, $ym)

            $xm = 1;
            $ym = 1;

            // Alter the offsets ($xo, $yo) and modifiers ($xm, $ym) according to the
            // location, $l

            switch ( $l )
            {
                case RoundedImage::TOP_LEFT:
                    $xm = -1;
#                    $ym = 1;
                    $xo += $r - 1;
#                    $yo = $yo;
                    break;
#                case RoundedImage::TOP_RIGHT:
#                    $xm = 1;
#                    $ym = 1;
#                    $xo = $xo;
#                    $yo = $yo;
#                    break;
                case RoundedImage::BOTTOM_LEFT:
                    $xm = -1;
                    $ym = -1;
                    $xo += $r - 1;
                    $yo += $r - 1;
                    break;
                case RoundedImage::BOTTOM_RIGHT:
#                    $xm = 1;
                    $ym = -1;
#                    $xo = $xo;
                    $yo += $r - 1;
                    break;
            }

            // To reduce the amount of operations performed in this section, the curve is
            // drawn in two halves. This means the $x start ($s) is not zero (as it would
            // be normally) but instead is where f(x,r) = r - x. Since this value is most
            // likely a float, we must floor it (round or ceil might cause us to not draw
            // the centre of the curve).

            $s = floor($r * 5 * sqrt(2) / 10);

            // Iterate through columns with $x

            for ( $x = $s; $x < $r; $x++ )
            {
                $fx = floor($this->f($x, $r));

                $y = $fx;
                while ( ($a = $this->a($x, $y, $r)) && $y < $r)
                {
                    $x1 = $xo + $xm * $x;
                    $y1 = $yo + $ym * $y;
                    $this->setPixelAlpha($x1, $y1, $a);

                    $x1 = $xo + $xm * ($r - $y - 1);
                    $y1 = $yo + $ym * ($r - $x - 1);
                    $this->setPixelAlpha($x1, $y1, $a);

                    $y++;
                }

                $y = $fx - 1;
                $x1 = $xo + $xm * $x;
                $x2 = $x1;
                $y1 = $yo + $ym * $y;
                $y2 = $yo;
                imageline($this->c, $x1, $y1, $x2, $y2, $this->b);

                $y = $fx;
                $x1 = $xo + $xm * ($r - $y);
                $x2 = $xo + $xm * ($r - 1);
                $y1 = $yo + $ym * ($r - $x - 1);
                $y2 = $y1;
                imageline($this->c, $x1, $y1, $x2, $y2, $this->b);
            }
        }

        // Sets the alpha value of a pixel ($x, $y) to $a.
        private function setPixelAlpha($x, $y, $a)
        {
            // Check that pixel ($x, $y) is valid

            if ( $x < 0 || $x > $this->w - 1 || $y < 0 || $y > $this->h - 1 )
                return;

            // Get RGBA colour of the pixel

            $c  = imagecolorat($this->c, $x, $y);

            // Split $c into RGB and A values

            $ea = ( $c & 0x7F000000 );
            $c  = ( $c & 0x00FFFFFF );

            // Scale $a against max alpha (127), add the existing alpha $ea, cap it at
            // 127 and then bump it to fi the bitmask 0x7F000000

            $a *= 127;
            $a += $ea;
            if ( $a > 127 ) $a = 127;
            $a = $a << 24;

            // Merge the alpha value with the RGB colour and set the new colour of the
            // pixel.

            $p = ( $a | $c );
            imagesetpixel($this->c, $x, $y, $p);
        }

        // f(x,r); is a U-shaped curve of radius r, about the point (0,r)
        private function f($x, $r)
        {
            return $r - sqrt(pow($r, 2) - pow($x, 2));
        }

        // g(x,r); is the inverse function of f(x,r)
        private function g($x, $r)
        {
            return sqrt(pow($r, 2) - pow(($r - $x), 2));
        }

        // h(x,r); is the integral of f(x,r) where r is != 0
        private function h($x, $r)
        {
            return $r*$x - ($x*sqrt(pow($r, 2) - pow($x, 2)))/2 - (pow($r,2)*asin($x/$r))/2;
        }

        // a(x,y,r); is the area of the region bounded by x >= x1, y >= y1, x =< x2,
        // y =< y2 and y =< f(x,r) where x1, y1, x2, y2 and r are all > 0
        private function a($x1, $y1, $r)
        {
            // Set the bounds of the region (x, y)

            $x2 = $x1 + 1;
            $y2 = $y1 + 1;

            // Calculate the x values that correspond to y1 and y2

            $gy1 = $this->g($y1, $r);
            $gy2 = $this->g($y2, $r);

            // Choose upper and lower bounds for x in the integration about to be done,
            // they must not be outside the region being investigated

            $b1 = ( $gy1 > $x1 ) ? ( $gy1 < $x2 ) ? $gy1 : $x2 : $x1;
            $b2 = ( $gy2 < $x2 ) ? ( $gy2 > $x1 ) ? $gy2 : $x1 : $x2;

            // Integrate using the bounds b1 and b2

            $h1 = $this->h($b1, $r);
            $h2 = $this->h($b2, $r);

            // Get the area under y=f(x,r) between b1 and b2 (1), subtract from that the
            // rectangular area below y=y1 (2), now add to that the rectangular area on
            // the right of x=b2 (3).
            // This method makes the assumption that f'(x,r) > 0, except at x=0.

            //     ____1____   ________2________   ____________3____________
            return $h2 - $h1 - ($b2 - $b1) * $y1 + ($y2 - $y1) * ($x2 - $b2);
        }
    }

?>
