<?php
/**
 * @package zesk
 * @subpackage contact
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

/*
 * Docs:
 *
 * USA:
 *	5 digits
 *  -or-
 *  5 digits-4 digits
 *
 * Jolly old England:
 *
 * 	http://www.royalmail.com:80/portal/rm/content1?catId=400044&mediaId=9200078
 *
 * 	A postcode is made up of a combination of letters and numbers in one of the following ways:
 *
 * 	A1 2BC
 * 	D34 5EF
 * 	GH6 7IJ
 * 	KL8M 9NO
 *
 * 	The same code is usually used for a small group of addresses so is not unique to every address but helps to pin-point exactly where the item of mail needs to go to.
 * 	Back to top>
 * 	2. How does a postcode work?
 *
 * Each part of the postcode provides step-by-step information about where the item of mail is heading. From left to right the postcode narrows down its destination.
 *
 * 	EC	1V	9	HQ
 * 	The first one or two letters is the postcode area and it identifies the main Royal Mail sorting office which will process the mail. In this case EC would go to the Mount Pleasant sorting office in London.
 *
 * 	The second part is usually just one or two numbers but for some parts of London it can be a number and a letter. This is the postcode district and tells the sorting office which delivery office the mail should go to.
 *
 * 	This third part is the sector and is usually just one number. This tells the delivery office which local area or neighbourhood the mail should go to.
 *
 * 	The final part of the postcode is the unit code which is always two letters. This identifies a group of up to 80 addresses and tells the delivery office which postal route (or walk) will deliver the item.
 *
 * So:
 *
 * 	[A-Z]{1,2}[A-Z0-9]{1,2}[0-9]?[A-Z]{2}
 */
class Contact_Address_Parser {
    /**
     * Pattern to match a company name
     */
    const RE_ADDRESS_COMPANY = '.+(\.com|\.net|\.org|L\.P\.|P\.C\.|LLC|inc|ltd|GmbH).*';

    /**
     * Pattern to match a city
     */
    const RE_ADDRESS_CITY = '[A-Za-z][A-Za-z\.\s]+';

    /**
     * Pattern to match a international state
     */
    const RE_ADDRESS_STATE = '[A-Za-z][A-Za-z\.\s]+';

    /**
     * Pattern to match a US state
     */
    const RE_ADDRESS_STATE_US = '[A-Za-z]{2}';

    /**
     * Pattern to match a Country Code or country name
     */
    const RE_ADDRESS_COUNTRY = '[A-Za-z]{2,} ?[A-Za-z]*';

    /**
     * Pattern to match a Zip code in the US
     */
    const RE_ADDRESS_ZIP_US = '[0-9]{5}(-?[0-9]{4})?';

    /**
     * Pattern to match a Zip code in Great Britain
     */
    const RE_ADDRESS_ZIP_GB = '[A-Za-z]{1,2}[A-Za-z0-9]{1,2} ?[0-9]?[a-zA-Z]{2}';

    /**
     * Pattern to match a Zip code in Canada
     */
    const RE_ADDRESS_ZIP_CA = '[A-Z0-9]{3} [A-Z0-9]{3}'; // Canada? Or just Ontario

    /**
     * Pattern to match a Zip code in all other countries?
     */
    const RE_ADDRESS_ZIP_OTHER = '[-A-Z0-9]{4,}';

    public static function parse(Application $application, $lines) {
        /**
         * Pattern to match a Zip code in all countries
         */
        $RE_ADDRESS_ZIP = self::RE_ADDRESS_ZIP_GB . '|' . self::RE_ADDRESS_ZIP_CA . '|' . self::RE_ADDRESS_ZIP_OTHER;

        // Ordering:
        //		Country items are first, as they will be rejected if the country is not found in the database
        //		Then from most limiting to least limiting patterns
        //		If a pattern is found and a member has been set already, it's skipped
        //		We parse the address from last line to first line
        $patterns = array(
            // Pre-filter things that look like company names so we don't think they are states etc.
            '/^(' . self::RE_ADDRESS_COMPANY . ')$/' => array(
                0 => "street",
            ),
            // Just a word: Try country code
            '/^(' . self::RE_ADDRESS_COUNTRY . ')$/' => array(
                0 => "country",
            ),
            // City, State Zip Country
            //[A-Za-z]{2,} country
            '/^(' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_STATE . ') (' . $RE_ADDRESS_ZIP . ') (' . self::RE_ADDRESS_COUNTRY . ')$/' => array(
                4 => "country",
                1 => "city",
                2 => "province",
                3 => "postal_code",
            ),
            // Zip City, State Country
            '/^(' . $RE_ADDRESS_ZIP . ') (' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_STATE . ') (' . self::RE_ADDRESS_COUNTRY . ')$/' => array(
                4 => "country",
                3 => "province",
                2 => "city",
                1 => "postal_code",
            ),
            // City, State Country
            '/^(' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_STATE . ') (' . self::RE_ADDRESS_COUNTRY . ')$/' => array(
                3 => "country",
                2 => "province",
                1 => "city",
            ),
            // Zip City Country
            '/^(' . $RE_ADDRESS_ZIP . ') (' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_COUNTRY . ')$/' => array(
                3 => "country",
                2 => "city",
                1 => "postal_code",
            ),
            // Zip Country
            '/^(' . $RE_ADDRESS_ZIP . ') (' . self::RE_ADDRESS_COUNTRY . ')$/' => array(
                2 => "country",
                1 => "postal_code",
            ),
            // Country Zip
            '/^(' . self::RE_ADDRESS_COUNTRY . ') (' . $RE_ADDRESS_ZIP . ')$/' => array(
                1 => "country",
                2 => "postal_code",
            ),
            // City, ST 12345
            // City, ST 12345-1234
            // City, ST 123451234
            '/^(' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_STATE_US . ') (' . self::RE_ADDRESS_ZIP_US . ')$/' => array(
                1 => "city",
                2 => "province",
                3 => "postal_code",
                "country" => "US",
            ),
            // City, State Zip
            '/^(' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_STATE . ') (' . $RE_ADDRESS_ZIP . ')$/' => array(
                1 => "city",
                2 => "province",
                3 => "postal_code",
            ),
            // Zip City, State
            '/^(' . $RE_ADDRESS_ZIP . ') (' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_STATE . ')$/' => array(
                1 => "postal_code",
                2 => "city",
                3 => "province",
            ),
            // Zip City, State Zip - Saw this once
            '/^(' . $RE_ADDRESS_ZIP . ') (' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_STATE . ') (' . $RE_ADDRESS_ZIP . ')$/' => array(
                1 => "postal_code",
                2 => "city",
                3 => "province",
            ),
            // City, State
            '/^(' . self::RE_ADDRESS_CITY . '),? (' . self::RE_ADDRESS_STATE . ')$/' => array(
                1 => "city",
                2 => "province",
            ),
            // Zip State
            '/^(' . $RE_ADDRESS_ZIP . ') (' . self::RE_ADDRESS_STATE . ')$/' => array(
                2 => "province",
                1 => "postal_code",
            ),
            // Zip
            '/^(' . $RE_ADDRESS_ZIP . ')$/' => array(
                1 => "postal_code",
            ),
        );

        if (is_string($lines)) {
            $lines = explode("\n", $lines);
        }
        $address = array(
            "unparsed" => implode("\n", $lines),
        );
        $streets = array();
        while (count($lines) !== 0) {
            $line = array_pop($lines);
            if (empty($line)) {
                continue;
            }
            $line = str_replace(",", ", ", $line);
            $line = preg_replace('/\s+/', " ", $line);

            $matched = false;
            foreach ($patterns as $pattern => $map) {
                $matches = false;
                if (!preg_match($pattern, $line, $matches)) {
                    continue;
                }
                foreach ($map as $k => $v) {
                    if (is_numeric($k)) {
                        assert(isset($matches[$k]));
                        $temp = $matches[$k];
                        $k = $v;
                        $v = $temp;
                        if ($k === "street") {
                            array_unshift($streets, $line);
                            $matched = true;

                            break;
                        } elseif ($k === "country") {
                            $cc = Country::find_country($application, $v);
                            if (empty($cc)) {
                                break;
                            }
                            if (!avalue($address, $k . '_id')) {
                                $address[$k . '_id'] = $cc->id();
                            }
                        } else {
                            if (!array_key_exists($k, $address)) {
                                $address[$k] = $v;
                            }
                        }
                        $matched = true;
                    } else {
                        if (array_key_exists($k, $address)) {
                            continue;
                        }
                        $address[$k] = $v;
                    }
                }
                if ($matched) {
                    break;
                }
            }
            if (!$matched) {
                array_unshift($streets, $line);
            }
        }
        $address['street'] = implode("\n", $streets);
        return $address;
    }
}
