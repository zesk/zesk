<?php
/**
 * @author kent@marketacumen.com
 * @copyright 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
abstract class Temporal {
    /**
     *
     * @var string
     */
    const UNIT_YEAR = "year";

    /*
     * @var string
     */
    const UNIT_QUARTER = "quarter";

    /*
     * @var string
     */
    const UNIT_MONTH = "month";

    /*
     * @var string
     */
    const UNIT_WEEKDAY = "weekday";

    /*
     * @var string
     */
    const UNIT_WEEK = "week";

    /*
     * @var string
     */
    const UNIT_DAY = "day";

    /*
     * @var string
     */
    const UNIT_HOUR = "hour";

    /*
     * @var string
     */
    const UNIT_MINUTE = "minute";

    /*
     * @var string
     */
    const UNIT_SECOND = "second";

    /*
     * @var string
     */
    const UNIT_MILLISECOND = "millisecond";

    /**
     * Duh.
     *
     * @var integer
     */
    const MILLISECONDS_PER_SECONDS = 1000;

    /**
     * @var integer
     */
    const SECONDS_PER_MINUTE = 60;

    /**
     * @var integer
     */
    const MINUTES_PER_HOUR = 60;

    /**
     * @var integer
     */
    const HOURS_PER_DAY = 24;

    /**
     * @var integer
     */
    const DAYS_PER_YEAR = 365.25; // Leap

    /**
     * @var integer
     */
    const MONTHS_PER_YEAR = 12;

    /**
     * @var integer
     */
    const DAYS_PER_WEEK = 7;

    /**
     *
     * @var integer
     * @todo PHP7 use calculation
     */
    const DAYS_PER_QUARTER = 91.3125; // self::DAYS_PER_YEAR / 4;

    /**
     * @var double
     * @todo PHP7 use calculation
     */
    const DAYS_PER_MONTH = 30.4375; // self::DAYS_PER_YEAR / self::MONTHS_PER_YEAR;

    /**
     * @var integer
     * @todo PHP7 use calculation
     */
    const SECONDS_PER_DAY = 86400; // self::SECONDS_PER_MINUTE * self::MINUTES_PER_HOUR * self::HOURS_PER_DAY;

    /**
     * @var integer
     * @todo PHP7 use calculation
     */
    const SECONDS_PER_WEEK = 604800; // self::SECONDS_PER_DAY * self::DAYS_PER_WEEK;

    /**
     *
     * @var double
     * @todo PHP7 use calculation
     */
    const SECONDS_PER_YEAR = 31557600; // self::SECONDS_PER_DAY * self::DAYS_PER_YEAR;

    /**
     *
     * @todo PHP7 use calculation
     * @var double
     */
    const SECONDS_PER_QUARTER = 7889400; // self::SECONDS_PER_DAY * self::DAYS_PER_QUARTER;

    /**
     *
     * @todo PHP7 use calculation
     * @var double
     */
    const SECONDS_PER_MONTH = 2629800; // self::SECONDS_PER_YEAR / self::MONTHS_PER_YEAR;

    /**
     *
     * @todo PHP7 use calculation
     * @var double
     */
    const SECONDS_PER_HOUR = 3600; // self::SECONDS_PER_MINUTE * self::MINUTES_PER_HOUR;

    /**
     * Translate units into seconds
     *
     * @var array
     */
    public static $UNITS_TRANSLATION_TABLE = array(
        self::UNIT_YEAR => self::SECONDS_PER_YEAR,
        self::UNIT_QUARTER => self::SECONDS_PER_QUARTER,
        self::UNIT_MONTH => self::SECONDS_PER_MONTH, // 365*86400/12 (average 30.42 days)
        self::UNIT_WEEK => self::SECONDS_PER_WEEK, // 60*60*24*7
        self::UNIT_DAY => self::SECONDS_PER_DAY, // 60*60*24
        self::UNIT_HOUR => self::SECONDS_PER_HOUR, // 60*60
        self::UNIT_MINUTE => self::SECONDS_PER_MINUTE, // 60
        self::UNIT_SECOND => 1, // 1:1
        self::UNIT_MILLISECOND => 0.001, // 1:1000
    );

    /**
     * Convert to SQL format
     *
     * @return string
     */
    abstract public function sql();

    /**
     * Format
     * @param string $format_string
     * @param array $options
     * @return string
     */
    abstract public function format(Locale $locale = null, $format_string = null, array $options = array());

    /**
     * Fetch formatting for this object
     *
     * @param array $options
     * @return array
     */
    abstract public function formatting(Locale $locale = null, array $options = array());

    /**
     * Return an array of unit => seconds (integer)
     *
     * @return array
     */
    public static function units_translation_table($unit = null) {
        $result = self::$UNITS_TRANSLATION_TABLE;
        if ($unit !== null) {
            return avalue($result, $unit, null);
        }
        return $result;
    }

    /**
     * Convert from seconds to a greater unit
     *
     * @param integer $seconds
     * @param string $unit
     * @throws Exception_Parameter
     * @return double
     */
    public static function convert_units($seconds, $unit = "second") {
        $seconds_in_unit = self::units_translation_table($unit);
        if ($seconds_in_unit === null) {
            throw new Exception_Parameter("Invalid unit name passed to {method}: {unit}", array(
                "method" => __METHOD__,
                "unit" => $unit,
            ));
        }
        return doubleval($seconds / $seconds_in_unit);
    }

    /**
     * Convert seconds into a particular unit
     *
     * @param integer $seconds
     *        	Number of seconds to convert to a unit.
     * @param string $stop_unit
     *        	Unit to stop comparing for. If you only want to know how many months away
     *        	something is,
     *        	specify a higher value.
     * @param double $fraction
     *        	Returns $seconds divided by total units, can be used to specify 2.435 years, for
     *        	example.
     * @return string The units closest to the number of seconds
     */
    public static function seconds_to_unit($seconds, $stop_unit = "second", &$fraction = null) {
        $seconds = intval($seconds);
        $translation = self::units_translation_table();
        foreach ($translation as $unit => $unit_seconds) {
            if (($seconds > $unit_seconds) || ($stop_unit === $unit)) {
                $fraction = intval($seconds / $unit_seconds);
                return $unit;
            }
        }
        $fraction = $seconds;
        return $unit;
    }
}
