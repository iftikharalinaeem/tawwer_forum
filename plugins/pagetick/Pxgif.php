<?php

/**
 * This file is part of Pxgif.
 *
 * @author Todd Burry
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @link https://github.com/tburry/pxgif Pxgif on github.
 * @license MIT
 */

/**
 * A class that generates 1 pixel gifs.
 */
class Pxgif {

    /**
     * Generates a 1 pixel gif as a data uri attribute.
     * @param string $gif_str A gif string generated with {@link Pxgif::gifStr} or {@link Pxgif::httpStr}.
     * @return string Returns the gif encoded as a data uri.
     */
    public static function dataUri($gif_str) {
        return 'data:image/gif;base64,'.base64_encode($gif_str);
    }

    /**
     * Generates a 1 pixel gif.
     * @param int $r Red.
     * @param int $g Green.
     * @param int $b Blue.
     * @param bool $transparent Whether or not the gif should be transparent.
     * @return string Returns the gif binary as a string.
     */
    public static function gifStr($r = 0, $g = 0, $b = 0, $transparent = true) {
        return pack('C*',
            71, 73, 70, 56, 57, 97, // "GIF89a"

            // Logical screen descriptor
            1, 0, // logical screen width
            1, 0, // logical screen height
            128, // global color table | color resolution(3) | sort flag | size of global color table (3)
            0, // background color index
            0, // pixel aspect ratio
            $r, $g, $b, 0, 0, 0, // global color table

            // Graphic control extension
            0x21, // extension introducer
            0xF9, // graphic control label
            4, // block size
            1, // disposal method(3) | user input(3) | transparency |
            0, 0, // delay time
            $transparent ? 0 : 1, // transparent color index
            0, // block terminator

            // Image descriptor
            0x2C, // image seperator
            0, 0, // image left position
            0, 0, // image top position
            1, 0, // image height
            1, 0, // image width
            0, // local color | interlaced | ordered | reserved(2) | size of color table (3)

            2, 2, 68, 1, 0, // image data

            59); // gif trailer
    }

    /**
     * Generates the gif string for an http status code.
     * @param int $code The http status code.
     * @param bool $transparent_ok Whether or not to return a transparent gif on 1xx or 2xx responses.
     * @return string Returns the gif binary as a string.
     */
    public static function httpStr($code = 200, $transparent_ok = false) {
        $code = (int)$code;

        $n1 = $code % 100;
        $n0 = floor($code / 100);
        $transparent = false;

        switch ($n0) {
            case 1:
                // Grey - 1xx Informational Responses
                $r = hexdec($n1);
                $g = $r;
                $b = $r;
                $transparent = $transparent_ok;
                break;
            case 2:
                // Green - 2xx Successful Responses
                $r = hexdec($n1);
                $g = 0xDD;
                $b = $r;
                $transparent = $transparent_ok;
                break;
            case 3:
                // Blue - 3xx Redirection Responses
                $r = hexdec($n1);
                $g = 0x7A;
                $b = 0xDD;
                break;
            case 4:
                // Orange - 4xx Client Error
                $r = 0xFF;
                $g = 0x8A;
                $b = hexdec($n1);
                break;
            case 5:
            default:
                // Red - 5xx Server Error
                $r = 0xDD;
                $g = 0x3F;
                $b = hexdec($n1);
                break;
        }

        return self::gifStr($r, $g, $b, $transparent);
    }
}
