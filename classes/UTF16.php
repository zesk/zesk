<?php
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 * Created on Wed Feb 24 14:17:02 EST 2010 14:17:02
 */
namespace zesk;

/**
 * 16-bit UTF utilities
 *
 * @author kent
 */
class UTF16 {
    /**
     * Convert a string from utf16 to utf8
     *
     * Thanks:
     * http://www.moddular.org/log/utf16-to-utf8
     * http://www.onicos.com/staff/iz/amuse/javascript/expert/utf.txt
     *
     * @param string $str
     * @return string
     */
    public static function to_utf8($str, &$be = null) {
        $c0 = ord($str[0]);
        $c1 = ord($str[1]);
        
        $found_be = false;
        if ($c0 == 0xFE && $c1 == 0xFF) {
            $be = true;
            $found_be = true;
        } elseif ($c0 == 0xFF && $c1 == 0xFE) {
            $be = false;
            $found_be = true;
        }
        if ($be === null) {
            $be = true;
        }
        $len = strlen($str);
        $dec = '';
        if ($len % 1 !== 0) {
            $odd = 1;
        }
        for ($i = $found_be ? 2 : 0; $i < $len; $i += 2) {
            $c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) : ord($str[$i + 1]) << 8 | ord($str[$i]);
            if ($c >= 0x0001 && $c <= 0x007F) {
                $dec .= chr($c);
            } elseif ($c > 0x07FF) {
                $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
                $dec .= chr(0x80 | (($c >> 6) & 0x3F));
                $dec .= chr(0x80 | (($c >> 0) & 0x3F));
            } else {
                $dec .= chr(0xC0 | (($c >> 6) & 0x1F));
                $dec .= chr(0x80 | (($c >> 0) & 0x3F));
            }
        }
        return $dec;
    }
    
    /**
     * Decode UTF-16 encoded strings.
     *
     * Can handle both BOM'ed data and un-BOM'ed data.
     * Assumes Big-Endian byte order if no BOM is available.
     * From: http://php.net/manual/en/function.utf8-decode.php
     *
     * @param   string  $str  UTF-16 encoded data to decode.
     * @return  string  UTF-8 / ISO encoded data.
     * @access  public
     * @version 0.1 / 2005-01-19
     * @author  Rasmus Andersson {@link http://rasmusandersson.se/}
     * @package Groupies
     */
    public static function decode($str, &$be = null) {
        if (strlen($str) < 2) {
            return $str;
        }
        $c0 = ord($str[0]);
        $c1 = ord($str[1]);
        $start = 0;
        if ($c0 == 0xFE && $c1 == 0xFF) {
            $be = true;
            $start = 2;
        } elseif ($c0 == 0xFF && $c1 == 0xFE) {
            $start = 2;
            $be = false;
        }
        if ($be === null) {
            $be = true;
        }
        $len = strlen($str);
        $newstr = '';
        for ($i = $start; $i < $len; $i += 2) {
            if ($be) {
                $val = ord($str[$i]) << 4;
                $val += ord($str[$i + 1]);
            } else {
                $val = ord($str[$i + 1]) << 4;
                $val += ord($str[$i]);
            }
            $newstr .= ($val == 0x228) ? "\n" : chr($val);
        }
        return $newstr;
    }
    
    /**
     * This is probably too simplistic, but should work for most standard ASCII < 0x7F
     * Used currently in CSVReader to convert delimiters
     *
     * @param string $str String to encode
     * @param boolean $be Big endian-encoding
     * @param boolean $add_bom Add byte-order marker
     * @return string Encoded in UTF-16
     */
    public static function encode($str, $be = true, $add_bom = true) {
        $n = strlen($str);
        $result = "";
        if ($add_bom) {
            $result .= $be ? chr(0xFE) . chr(0xFF) : chr(0xFF) . chr(0xFE);
        }
        if ($be) {
            for ($i = 0; $i < $n; $i++) {
                $c = ord($str[$i]);
                $result .= chr(0x00) . chr($c);
            }
        } else {
            for ($i = 0; $i < $n; $i++) {
                $c = ord($str[$i]);
                $result .= chr($c) . chr(0x00);
            }
        }
        return $result;
    }

    public static function to_iso8859($mixed, &$be = null) {
        if (is_array($mixed)) {
            foreach ($mixed as $k => $v) {
                $mixed[$k] = UTF16::decode($v, $be);
            }
            return $mixed;
        }
        return UTF16::decode($mixed, $be);
    }
}
