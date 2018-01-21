<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/world/classes/Country.php $
 * @package zesk
 * @subpackage objects
 */
namespace zesk;

/**
 * @author kent
 * @see Class_Country
 * @property id $id
 * @property string $code
 * @property string $name
 */
class Country extends ORM {
	public static function find_country(Application $application, $mixed) {
		if (is_numeric($mixed)) {
			$c = new Country($application, $mixed);
			if ($c->fetch()) {
				return $c;
			}
		} else {
			$c = new Country($application, array(
				"code" => $mixed
			));
			return $c->find();
		}
		return null;
	}
	public static function country_codes(Locale $locale = null) {
		$country_codes = array(
			'ad' => 'Andorra',
			'ae' => 'United Arab Emirates',
			'af' => 'Afghanistan',
			'ag' => 'Antigua and Barbuda',
			'ai' => 'Anguilla',
			'al' => 'Albania',
			'am' => 'Armenia',
			'an' => 'Netherlands Antilles',
			'ao' => 'Angola',
			'aq' => 'Antarctica',
			'ar' => 'Argentina',
			'as' => 'American Samoa',
			'at' => 'Austria',
			'au' => 'Australia',
			'aw' => 'Aruba',
			'ax' => 'Aland Islands',
			'az' => 'Azerbaijan',
			'ba' => 'Bosnia and Herzegovina',
			'bb' => 'Barbados',
			'bd' => 'Bangladesh',
			'be' => 'Belgium',
			'bf' => 'Burkina Faso',
			'bg' => 'Bulgaria',
			'bh' => 'Bahrain',
			'bi' => 'Burundi',
			'bj' => 'Benin',
			'bm' => 'Bermuda',
			'bn' => 'Brunei',
			'bo' => 'Bolivia',
			'br' => 'Brazil',
			'bs' => 'Bahamas',
			'bt' => 'Bhutan',
			'bv' => 'Bouvet Island',
			'bw' => 'Botswana',
			'by' => 'Belarus',
			'bz' => 'Belize',
			'ca' => 'Canada',
			'cc' => 'Cocos (Keeling Islands)',
			'cd' => 'Congo (Kinshasa)',
			'cf' => 'Central African Republic',
			'cg' => 'Congo (Brazzaville)',
			'ch' => 'Switzerland',
			'ci' => 'Ivory Coast',
			'ck' => 'Cook Islands',
			'cl' => 'Chile',
			'cm' => 'Cameroon',
			'cn' => 'China',
			'co' => 'Colombia',
			'cr' => 'Costa Rica',
			'cs' => 'Serbia And Montenegro',
			'cu' => 'Cuba',
			'cv' => 'Cape Verde',
			'cx' => 'Christmas Island',
			'cy' => 'Cyprus',
			'cz' => 'Czech Republic',
			'de' => 'Germany',
			'dj' => 'Djibouti',
			'dk' => 'Denmark',
			'dm' => 'Dominica',
			'do' => 'Dominican Republic',
			'dz' => 'Algeria',
			'ec' => 'Ecuador',
			'ee' => 'Estonia',
			'eg' => 'Egypt',
			'eh' => 'Western Sahara',
			'er' => 'Eritrea',
			'es' => 'Spain',
			'et' => 'Ethiopia',
			'fi' => 'Finland',
			'fj' => 'Fiji',
			'fk' => 'Falkland Islands',
			'fm' => 'Micronesia',
			'fo' => 'Faroe Islands',
			'fr' => 'France',
			'ga' => 'Gabon',
			'gd' => 'Grenada',
			'ge' => 'Georgia',
			'gf' => 'French Guiana',
			'gg' => 'Guernsey',
			'gh' => 'Ghana',
			'gi' => 'Gibraltar',
			'gl' => 'Greenland',
			'gm' => 'Gambia',
			'gn' => 'Guinea',
			'gp' => 'Guadeloupe',
			'gq' => 'Equatorial Guinea',
			'gr' => 'Greece',
			'gs' => 'South Georgia and the South Sandwich Islands',
			'gt' => 'Guatemala',
			'gu' => 'Guam',
			'gw' => 'Guinea-Bissau',
			'gy' => 'Guyana',
			'hk' => 'Hong Kong S.A.R., China',
			'hm' => 'Heard Island and McDonald Islands',
			'hn' => 'Honduras',
			'hr' => 'Croatia',
			'ht' => 'Haiti',
			'hu' => 'Hungary',
			'id' => 'Indonesia',
			'ie' => 'Ireland',
			'il' => 'Israel',
			'im' => 'Isle of Man',
			'in' => 'India',
			'io' => 'British Indian Ocean Territory',
			'iq' => 'Iraq',
			'ir' => 'Iran',
			'is' => 'Iceland',
			'it' => 'Italy',
			'je' => 'Jersey',
			'jm' => 'Jamaica',
			'jo' => 'Jordan',
			'jp' => 'Japan',
			'ke' => 'Kenya',
			'kg' => 'Kyrgyzstan',
			'kh' => 'Cambodia',
			'ki' => 'Kiribati',
			'km' => 'Comoros',
			'kn' => 'Saint Kitts and Nevis',
			'kp' => 'North Korea',
			'kr' => 'South Korea',
			'kw' => 'Kuwait',
			'ky' => 'Cayman Islands',
			'kz' => 'Kazakhstan',
			'la' => 'Laos',
			'lb' => 'Lebanon',
			'lc' => 'Saint Lucia',
			'li' => 'Liechtenstein',
			'lk' => 'Sri Lanka',
			'lr' => 'Liberia',
			'ls' => 'Lesotho',
			'lt' => 'Lithuania',
			'lu' => 'Luxembourg',
			'lv' => 'Latvia',
			'ly' => 'Libya',
			'ma' => 'Morocco',
			'mc' => 'Monaco',
			'md' => 'Moldova',
			'me' => 'Montenegro',
			'mg' => 'Madagascar',
			'mh' => 'Marshall Islands',
			'mk' => 'Macedonia',
			'ml' => 'Mali',
			'mm' => 'Myanmar',
			'mn' => 'Mongolia',
			'mo' => 'Macao S.A.R., China',
			'mp' => 'Northern Mariana Islands',
			'mq' => 'Martinique',
			'mr' => 'Mauritania',
			'ms' => 'Montserrat',
			'mt' => 'Malta',
			'mu' => 'Mauritius',
			'mv' => 'Maldives',
			'mw' => 'Malawi',
			'mx' => 'Mexico',
			'my' => 'Malaysia',
			'mz' => 'Mozambique',
			'na' => 'Namibia',
			'nc' => 'New Caledonia',
			'ne' => 'Niger',
			'nf' => 'Norfolk Island',
			'ng' => 'Nigeria',
			'ni' => 'Nicaragua',
			'nl' => 'Netherlands',
			'no' => 'Norway',
			'np' => 'Nepal',
			'nr' => 'Nauru',
			'nu' => 'Niue',
			'nz' => 'New Zealand',
			'om' => 'Oman',
			'pa' => 'Panama',
			'pe' => 'Peru',
			'pf' => 'French Polynesia',
			'pg' => 'Papua New Guinea',
			'ph' => 'Philippines',
			'pk' => 'Pakistan',
			'pl' => 'Poland',
			'pm' => 'Saint Pierre and Miquelon',
			'pn' => 'Pitcairn',
			'pr' => 'Puerto Rico',
			'ps' => 'Palestinian Territory',
			'pt' => 'Portugal',
			'pw' => 'Palau',
			'py' => 'Paraguay',
			'qa' => 'Qatar',
			're' => 'Reunion',
			'ro' => 'Romania',
			'rs' => 'Serbia',
			'ru' => 'Russia',
			'rw' => 'Rwanda',
			'sa' => 'Saudi Arabia',
			'sb' => 'Solomon Islands',
			'sc' => 'Seychelles',
			'sd' => 'Sudan',
			'se' => 'Sweden',
			'sg' => 'Singapore',
			'sh' => 'Saint Helena',
			'si' => 'Slovenia',
			'sj' => 'Svalbard and Jan Mayen',
			'sk' => 'Slovakia',
			'sl' => 'Sierra Leone',
			'sm' => 'San Marino',
			'sn' => 'Senegal',
			'so' => 'Somalia',
			'sr' => 'Suriname',
			'st' => 'Sao Tome and Principe',
			'sv' => 'El Salvador',
			'sy' => 'Syria',
			'sz' => 'Swaziland',
			'tc' => 'Turks and Caicos Islands',
			'td' => 'Chad',
			'tf' => 'French Southern Territories',
			'tg' => 'Togo',
			'th' => 'Thailand',
			'tj' => 'Tajikistan',
			'tk' => 'Tokelau',
			'tl' => 'Timor-Leste',
			'tm' => 'Turkmenistan',
			'tn' => 'Tunisia',
			'to' => 'Tonga',
			'tr' => 'Turkey',
			'tt' => 'Trinidad and Tobago',
			'tv' => 'Tuvalu',
			'tw' => 'Taiwan',
			'tz' => 'Tanzania',
			'ua' => 'Ukraine',
			'ug' => 'Uganda',
			'uk' => 'United Kingdom',
			'um' => 'United States Minor Outlying Islands',
			'us' => 'United States',
			'uy' => 'Uruguay',
			'uz' => 'Uzbekistan',
			'va' => 'Vatican',
			'vc' => 'Saint Vincent and the Grenadines',
			've' => 'Venezuela',
			'vg' => 'British Virgin Islands',
			'vi' => 'U.S. Virgin Islands',
			'vn' => 'Vietnam',
			'vu' => 'Vanuatu',
			'wf' => 'Wallis and Futuna',
			'ws' => 'Samoa',
			'ye' => 'Yemen',
			'yt' => 'Mayotte',
			'za' => 'South Africa',
			'zm' => 'Zambia',
			'zw' => 'Zimbabwe'
		);
		if ($locale !== null) {
			return $locale->__($country_codes);
		}
		return $country_codes;
	}
}

