<?php
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Thu Apr 15 17:38:33 EDT 2010 17:38:33
 */
namespace zesk;

class Locale_FR extends Locale {
    public function date_format() {
        return "le {DDD} {MMMM} {YYYY}";
    }

    public function datetime_format() {
        return "{DDD} {MMMM} {YYYY}, {hh}:{mm}:{ss}";
    }

    public function time_format($include_seconds = false) {
        return $include_seconds ? "{hh}:{mm}:{ss}" : "{hh}:{mm}";
    }

    public function noun_semantic_plural($word, $count = 2) {
        return $count !== 1 ? "$word" . "s" : $word;
    }

    public function indefinite_article($word, $caps = false, $gender = "n") {
        // TODO Standarize this syntax for all languages which have gender and use standard tokens; add to API in Locale!
        $gender = $this->__("$word.gender");
        if (!$gender) {
            $gender = "m";
        }
        $article = ($gender === "f") ? "une" : "un";
        return ($caps ? ucfirst($article) : $article) . " " . $word;
    }

    public function possessive($owner, $object) {
        return "$object de $owner";
    }

    public function ordinal($n) {
        // TODO: Check this
        if ($n === 1) {
            return $n . "r";
        }
        return $n . "e";
    }

    public function negate_word($word, $preferred_prefix = null) {
        if ($preferred_prefix === null) {
            $preferred_prefix = "pas de";
        }
        return StringTools::case_match("pas de " . $word, $word);
    }
}
