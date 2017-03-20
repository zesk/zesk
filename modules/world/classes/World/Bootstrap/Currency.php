<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/world/classes/World/Bootstrap/Currency.php $
 * @package zesk
 * @subpackage model
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Currency
 * @author kent
 *
 */
class World_Bootstrap_Currency extends Options {
	
	/**
	 *
	 * @var array
	 */
	private $include_currency = null;
	
	/**
	 *
	 * @var array
	 */
	private $include_country = null;
	
	/**
	 *
	 * @param array $options
	 * @return World_Bootstrap_Currency
	 */
	public static function factory(array $options = array()) {
		return zesk()->objects->factory(__CLASS__, $options);
	}
	
	/**
	 * @global Module_World::include_currency List of currency codes to include
	 * @global Module_World::include_country List of country codes to include
	 *
	 * @param mixed $options
	 */
	public function __construct($options) {
		parent::__construct($options);
		$this->inherit_global_options("Module_World");
		$include_currency = $this->option("include_currency");
		if ($include_currency) {
			$this->include_currency = array_change_key_case(arr::flip_assign(to_list($include_currency), true));
		}
		$include_country = $this->option("include_country");
		if ($include_country) {
			$this->include_country = array_change_key_case(arr::flip_assign(to_list($include_country), true));
		}
	}
	public function bootstrap(Application $application) {
		$x = $application->object_factory(str::unprefix(__CLASS__, "World_Bootstrap_"));
		
		if ($this->option_bool("drop")) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}
		$codes = self::_codes();
		foreach ($codes as $row) {
			$this->process_row($application, $row);
		}
	}
	private function is_included(Currency $currency) {
		if ($this->include_country) {
			if ($currency->member_is_empty("bank_country")) {
				return false;
			}
			return avalue($this->include_country, strtolower($currency->bank_country->code), false);
		}
		if ($this->include_currency) {
			$ccode = strtolower($currency->code);
			return avalue($this->include_currency, $ccode, false);
		}
		return true;
	}
	private function process_row(Application $application, array $row) {
		list($country_name, $name, $codeName, $code_number, $symbol, $fractional_string) = $row;
		
		$name = $row[0];
		$code = strtolower(substr($codeName, 0, 2));
		
		$country = $application->object_factory('Country', array(
			'code' => $code,
			'name' => $name
		));
		if (!$country->find()) {
			if (!empty($name)) {
				if (!$country->find(array(
					'name' => $name
				))) {
					zesk()->logger->warning("Unable to find country {name} in database", array(
						"name" => $name
					));
					$country = null;
				}
			} else {
				$country = null;
			}
		}
		$fields["bank_country"] = $country;
		if (empty($symbol)) {
			$symbol = $codeName;
		}
		
		$fields["id"] = $id = intval($row[3]);
		$fields["name"] = $row[1];
		$fields["code"] = $codeName;
		$fields["symbol"] = $symbol;
		list($fractional, $units) = pair($fractional_string, " ");
		$fields["fractional"] = intval($fractional);
		$fields["fractional_units"] = $units;
		$fields["format"] = "{symbol}{amount}";
		
		if (empty($id)) {
			zesk()->logger->debug("Unknown id for currency {code} {name}", $fields);
			return null;
		}
		
		$currency = new Currency($fields);
		if ($this->is_included($currency)) {
			if ($this->option_bool("delete_mismatched_ids")) {
				$found = $currency->find();
				if ($found->id() !== $id) {
					$currency->delete();
					$currency->set_member($fields)->store();
				} else {
					return $currency->store();
				}
			} else {
				return $currency->register()->set_member($fields)->store();
			}
		}
		return null;
	}
	static private function _codes() {
		return array(
			array(
				'Afghanistan',
				'Afghani',
				'AFA',
				4,
				'Af',
				'100 puls'
			),
			array(
				'Albania',
				'Lek',
				'ALL',
				8,
				'L',
				'100 qindarka (qintars)'
			),
			array(
				'Algeria',
				'Algerian Dinar',
				'DZD',
				12,
				'DA',
				'100 centimes'
			),
			array(
				'American Samoa',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Andorra',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Angola',
				'New Kwanza',
				'AON',
				24,
				'Kz',
				'100 lwei'
			),
			array(
				'Anguilla',
				'East Caribbean Dollar',
				'XCD',
				951,
				'EC$',
				'100 cents'
			),
			array(
				'Antarctica',
				'No universal currency',
				'',
				'',
				'',
				''
			),
			array(
				'Antigua and Barbuda',
				'East Caribbean Dollar',
				'XCD',
				951,
				'EC$',
				'100 cents'
			),
			array(
				'Argentina',
				'Argentine Peso',
				'ARS',
				32,
				'$',
				'100 centavos'
			),
			array(
				'Armenia',
				'Armenian Dram',
				'AMD',
				51,
				'',
				'100 luma'
			),
			array(
				'Aruba',
				'Aruban Guilder',
				'AWG',
				533,
				'Af.',
				'100 cents'
			),
			array(
				'Australia',
				'Australian Dollar',
				'AUD',
				36,
				'A$',
				'100 cents'
			),
			array(
				'Austria',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Azerbaijan',
				'Azerbaijanian Manat',
				'AZM',
				31,
				'',
				'100 gopik'
			),
			array(
				'Bahamas',
				'Bahamian Dollar',
				'BSD',
				44,
				'B$',
				'100 cents'
			),
			array(
				'Bahrain',
				'Bahraini Dinar',
				'BHD',
				48,
				'BD',
				'1000 fils'
			),
			array(
				'Bangladesh',
				'Taka',
				'BDT',
				50,
				'Tk',
				'100 paisa (poisha)'
			),
			array(
				'Barbados',
				'Barbados Dollar',
				'BBD',
				52,
				'Bds$',
				'100 cents'
			),
			array(
				'Belarus',
				'Belarussian Ruble',
				'BYR',
				974,
				'BR',
				''
			),
			array(
				'Belgium',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Belize',
				'Belize Dollar',
				'BZD',
				84,
				'BZ$',
				'100 cents'
			),
			array(
				'Benin',
				'CFA Franc BCEAO',
				'XOF',
				952,
				'CFAF',
				'100 centimes'
			),
			array(
				'Bermuda',
				'Bermudian Dollar',
				'BMD',
				60,
				'Bd$',
				'100 cents'
			),
			array(
				'Bhutan',
				'Ngultrum',
				'BTN',
				64,
				'Nu',
				'100 chetrum'
			),
			array(
				'Bhutan',
				'Indian Rupee',
				'INR',
				356,
				'Rs',
				'100 paise'
			),
			array(
				'Bosnia and Herzegovina',
				'Convertible Marks',
				'BAM',
				977,
				'KM',
				'100 fennig'
			),
			array(
				'Botswana',
				'Pula',
				'BWP',
				72,
				'P',
				'100 thebe'
			),
			array(
				'Bouvet Island',
				'Norwegian Krone',
				'NOK',
				578,
				'NKr',
				'100 &oslash;re'
			),
			array(
				'Brazil',
				'Brazilian Real',
				'BRL',
				986,
				'R$',
				'100 centavos'
			),
			array(
				'British Indian Ocean Territory',
				'Pound Sterling',
				'GBP',
				826,
				'&pound;',
				'100 pence'
			),
			array(
				'British Indian Ocean Territory',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Brunei Darussalam',
				'Brunei Dollar',
				'BND',
				96,
				'B$',
				'100 sen (a.k.a. 100 cents)'
			),
			array(
				'Bulgaria',
				'Lev',
				'BGL',
				100,
				'Lv',
				'100 stotinki'
			),
			array(
				'Burkina Faso',
				'CFA Franc BCEAO',
				'XOF',
				952,
				'CFAF',
				'100 centimes'
			),
			array(
				'Burundi',
				'Burundi Franc',
				'BIF',
				108,
				'FBu',
				'100 centimes'
			),
			array(
				'Cambodia',
				'Riel',
				'KHR',
				116,
				'CR',
				'100 sen'
			),
			array(
				'Cameroon',
				'CFA Franc BEAC',
				'XAF',
				950,
				'CFAF',
				'100 centimes'
			),
			array(
				'Canada',
				'Canadian Dollar',
				'CAD',
				124,
				'Can$',
				'100 cents'
			),
			array(
				'Cape Verde',
				'Cape Verde Escudo',
				'CVE',
				132,
				'C.V.Esc.',
				'100 centavos'
			),
			array(
				'Cayman Islands',
				'Cayman Islands Dollar',
				'KYD',
				136,
				'CI$',
				'100 cents'
			),
			array(
				'Central African Republic',
				'CFA Franc BEAC',
				'XAF',
				950,
				'CFAF',
				'100 centimes'
			),
			array(
				'Chad',
				'CFA Franc BEAC',
				'XAF',
				950,
				'CFAF',
				'100 centimes'
			),
			array(
				'Chile',
				'Chilean Peso',
				'CLP',
				152,
				'Ch$',
				'100 centavos'
			),
			array(
				'China',
				'Yuan Renminbi',
				'CNY',
				156,
				'Y',
				'10 jiao = 100 fen'
			),
			array(
				'Christmas Island',
				'Australian Dollar',
				'AUD',
				36,
				'A$',
				'100 cents'
			),
			array(
				'Cocos (Keeling) Islands',
				'Australian Dollar',
				'AUD',
				36,
				'A$',
				'100 cents'
			),
			array(
				'Colombia',
				'Colombian Peso',
				'COP',
				170,
				'Col$',
				'100 centavos'
			),
			array(
				'Comoros',
				'Comoro Franc',
				'KMF',
				174,
				'CF',
				''
			),
			array(
				'Republic of the Congo',
				'CFA Franc BEAC',
				'XAF',
				950,
				'CFAF',
				'100 centimes'
			),
			array(
				'Democratic Republic of the Congo',
				'Franc Congolais',
				'CDF',
				976,
				'',
				'100 centimes'
			),
			array(
				'Cook Islands',
				'New Zealand Dollar',
				'NZD',
				554,
				'NZ$',
				'100 cents'
			),
			array(
				'Costa Rica',
				'Costa Rican Colon',
				'CRC',
				188,
				'&#8353;',
				'100 centimos'
			),
			array(
				"C&ocirc;te D'ivoire",
				'CFA Franc BCEAO',
				'XOF',
				952,
				'CFAF',
				'100 centimes'
			),
			array(
				'Croatia',
				'Kuna',
				'HRK',
				191,
				'HRK',
				'100 lipas'
			),
			array(
				'Cuba',
				'Cuban Peso',
				'CUP',
				192,
				'Cu$',
				'100 centavos'
			),
			array(
				'Cyprus',
				'Cyprus Pound',
				'CYP',
				196,
				'&pound;C',
				'100 cents'
			),
			array(
				'Cyprus - TRNC',
				'Turkish Lira',
				'TRL',
				792,
				'TL',
				'100 kurus'
			),
			array(
				'Czech Republic',
				'Czech Koruna',
				'CZK',
				203,
				'Kc (with hacek on c)',
				'100 haleru'
			),
			array(
				'Denmark',
				'Danish Krone',
				'DKK',
				208,
				'Dkr',
				'100 &oslash;re'
			),
			array(
				'Djibouti',
				'Djibouti Franc',
				'DJF',
				262,
				'DF',
				'100 centimes'
			),
			array(
				'Dominica',
				'East Caribbean Dollar',
				'XCD',
				951,
				'EC$',
				'100 cents'
			),
			array(
				'Dominican Republic',
				'Dominican Peso',
				'DOP',
				214,
				'RD$',
				'100 centavos'
			),
			array(
				'East Timor',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Ecuador',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Egypt',
				'Egyptian Pound',
				'EGP',
				818,
				'&pound;E',
				'100 piasters or 1000 milliemes'
			),
			array(
				'El Salvador',
				'El Salvador Colon',
				'SVC',
				222,
				'&cent;',
				'100 centavos'
			),
			array(
				'Equatorial Guinea',
				'CFA Franc BEAC',
				'XAF',
				950,
				'CFAF',
				'100 centimos'
			),
			array(
				'Estonia',
				'Kroon',
				'EEK',
				233,
				'Nfa',
				'100 cents'
			),
			array(
				'Eritrea',
				'Nakfa',
				'ERN',
				232,
				'KR',
				'100 senti'
			),
			array(
				'Ethiopia',
				'Ethiopian Birr',
				'ETB',
				230,
				'Br',
				'100 cents'
			),
			array(
				'European Union',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Faeroe Islands',
				'Danish Krone',
				'DKK',
				208,
				'Dkr',
				'100 &oslash;re'
			),
			array(
				'Falkland Islands (Malvinas)',
				'Pound',
				'FKP',
				238,
				'&pound;F',
				'100 pence'
			),
			array(
				'Fiji',
				'Fiji Dollar',
				'FJD',
				242,
				'F$',
				'100 cents'
			),
			array(
				'Finland',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'France',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'French Guiana',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'French Polynesia',
				'CFP Franc',
				'XPF',
				953,
				'CFPF',
				'100 centimes'
			),
			array(
				'French Southern Territories',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Gabon',
				'CFA Franc BEAC',
				'XAF',
				950,
				'CFAF',
				'100 centimes'
			),
			array(
				'Gambia',
				'Dalasi',
				'GMD',
				270,
				'D',
				'100 butut'
			),
			array(
				'Georgia',
				'Lari',
				'GEL',
				981,
				'',
				'100 tetri'
			),
			array(
				'Germany',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Ghana',
				'Cedi',
				'GHC',
				288,
				'&cent;',
				'100 psewas'
			),
			array(
				'Gibraltar',
				'Gibraltar Pound',
				'GIP',
				292,
				'&pound;G',
				'100 pence'
			),
			array(
				'Greece',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Greenland',
				'Danish Krone',
				'DKK',
				208,
				'Dkr',
				'100 &oslash;re'
			),
			array(
				'Grenada',
				'East Caribbean Dollar',
				'XCD',
				951,
				'EC$',
				'100 cents'
			),
			array(
				'Guadeloupe',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Guam',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Guatemala',
				'Quetzal',
				'GTQ',
				320,
				'Q',
				'100 centavos'
			),
			array(
				'Guinea',
				'Guinea Franc',
				'GNF',
				324,
				'',
				''
			),
			array(
				'Guinea-Bissau',
				'CFA Franc BCEAO',
				'XOF',
				952,
				'CFAF',
				'100 centimes'
			),
			array(
				'Guyana',
				'Guyana Dollar',
				'GYD',
				328,
				'G$',
				'100 cents'
			),
			array(
				'Haiti',
				'Gourde',
				'HTG',
				332,
				'G',
				'100 centimes'
			),
			array(
				'Haiti',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Heard and Mcdonald Islands',
				'Australian Dollar',
				'AUD',
				36,
				'A$',
				'100 cents'
			),
			array(
				'Honduras',
				'Lempira',
				'HNL',
				340,
				'L',
				'100 centavos'
			),
			array(
				'Hong Kong',
				'Hong Kong Dollar',
				'HKD',
				344,
				'HK$',
				'100 cents'
			),
			array(
				'Hungary',
				'Forint',
				'HUF',
				348,
				'Ft',
				'-none-'
			),
			array(
				'Iceland',
				'Iceland Krona',
				'ISK',
				352,
				'IKr',
				'100 aurar (sg. aur)'
			),
			array(
				'India',
				'Indian Rupee',
				'INR',
				356,
				'Rs',
				'100 paise'
			),
			array(
				'Indonesia',
				'Rupiah',
				'IDR',
				360,
				'Rp',
				'100 sen (no longer used)'
			),
			array(
				'International Monetary Fund (Imf)',
				'SDR',
				'XDR',
				960,
				'SDR',
				''
			),
			array(
				'Iran (Islamic Republic Of)',
				'Iranian Rial',
				'IRR',
				364,
				'Rls',
				'10 rials = 1 toman'
			),
			array(
				'Iraq',
				'Iraqi Dinar',
				'IQD',
				368,
				'ID',
				'1000 fils'
			),
			array(
				'Ireland',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Israel',
				'New Israeli Sheqel',
				'ILS',
				376,
				'NIS',
				'100 new agorot'
			),
			array(
				'Italy',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Jamaica',
				'Jamaican Dollar',
				'JMD',
				388,
				'J$',
				'100 cents'
			),
			array(
				'Japan',
				'Yen',
				'JPY',
				392,
				'&yen;',
				'100 sen (not used)'
			),
			array(
				'Jersey',
				'Pound Sterling',
				'GBP',
				826,
				'&pound;',
				'100 pence'
			),
			array(
				'Jordan',
				'Jordanian Dinar',
				'JOD',
				400,
				'JD',
				'1000 fils'
			),
			array(
				'Kazakhstan',
				'Tenge',
				'KZT',
				398,
				'',
				'100 tiyn'
			),
			array(
				'Kenya',
				'Kenyan Shilling',
				'KES',
				404,
				'K Sh',
				'100 cents'
			),
			array(
				'Kiribati',
				'Australian Dollar',
				'AUD',
				36,
				'A$',
				'100 cents'
			),
			array(
				'Korea, Democratic People\'s Republic Of',
				'North Korean Won',
				'KPW',
				408,
				'Wn',
				'100 chon'
			),
			array(
				'Korea, Republic Of',
				'Won',
				'KRW',
				410,
				'W',
				'100 chon'
			),
			array(
				'Kuwait',
				'Kuwaiti Dinar',
				'KWD',
				414,
				'KD',
				'1000 fils'
			),
			array(
				'Kyrgyzstan',
				'Som',
				'KGS',
				417,
				'',
				'100 tyyn'
			),
			array(
				'Lao People\'s Democratic Republic',
				'Kip',
				'LAK',
				418,
				'KN',
				'100 at'
			),
			array(
				'Latvia',
				'Latvian Lats',
				'LVL',
				428,
				'Ls',
				'100 santims'
			),
			array(
				'Lebanon',
				'Lebanese Pound',
				'LBP',
				422,
				'L.L.',
				'100 piastres'
			),
			array(
				'Lesotho',
				'Loti',
				'LSL',
				426,
				'L, pl., M',
				'100 lisente'
			),
			array(
				'Lesotho',
				'Rand',
				'ZAR',
				710,
				'R',
				'100 cents'
			),
			array(
				'Liberia',
				'Liberian Dollar',
				'LRD',
				430,
				'$',
				'100 cents'
			),
			array(
				'Libyan Arab Jamahiriya',
				'Libyan Dinar',
				'LYD',
				434,
				'LD',
				'1000 dirhams'
			),
			array(
				'Liechtenstein',
				'Swiss Franc',
				'CHF',
				756,
				'SwF',
				'100 rappen/centimes'
			),
			array(
				'Lithuania',
				'Lithuanian Litas',
				'LTL',
				440,
				'',
				'100 centu'
			),
			array(
				'Luxembourg',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Macau',
				'Pataca',
				'MOP',
				446,
				'P',
				'100 avos'
			),
			array(
				'Macedonia, The Former Yugoslav Republic Of',
				'Denar',
				'MKD',
				807,
				'MKD',
				'100 deni'
			),
			array(
				'Madagascar',
				'Malagasy Franc',
				'MGF',
				450,
				'FMG',
				'1 francs = 100 centimes'
			),
			array(
				'Malawi',
				'Kwacha',
				'MWK',
				454,
				'MK',
				'100 tambala'
			),
			array(
				'Malaysia',
				'Malaysian Ringgit',
				'MYR',
				458,
				'RM',
				'100 sen'
			),
			array(
				'Maldives',
				'Rufiyaa',
				'MVR',
				462,
				'Rf',
				'100 lari'
			),
			array(
				'Mali',
				'CFA Franc BCEAO',
				'XOF',
				952,
				'CFAF',
				'100 centimes'
			),
			array(
				'Malta',
				'Maltese Lira',
				'MTL',
				470,
				'Lm',
				'100 cents'
			),
			array(
				'Marshall Islands',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Martinique',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Mauritania',
				'Ouguiya',
				'MRO',
				478,
				'UM',
				'5 khoums'
			),
			array(
				'Mauritius',
				'Mauritius Rupee',
				'MUR',
				480,
				'Mau Rs',
				'100 cents'
			),
			array(
				'Mexico',
				'Mexican Peso',
				'MXN',
				484,
				'Mex$',
				'100 centavos'
			),
			array(
				'Micronesia',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Moldova, Republic Of',
				'Moldovan Leu',
				'MDL',
				498,
				'',
				''
			),
			array(
				'Monaco',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Mongolia',
				'Tugrik',
				'MNT',
				496,
				'Tug',
				'100 mongos'
			),
			array(
				'Montserrat',
				'East Caribbean Dollar',
				'XCD',
				951,
				'EC$',
				'100 cents'
			),
			array(
				'Morocco',
				'Moroccan Dirham',
				'MAD',
				504,
				'DH',
				'100 centimes'
			),
			array(
				'Mozambique',
				'Metical',
				'MZM',
				508,
				'Mt',
				'100 centavos'
			),
			array(
				'Myanmar',
				'Kyat',
				'MMK',
				104,
				'K',
				'100 pyas'
			),
			array(
				'Namibia',
				'Namibia Dollar',
				'NAD',
				516,
				'N$',
				'100 cents'
			),
			array(
				'Namibia',
				'Rand',
				'ZAR',
				710,
				'R',
				'100 cents'
			),
			array(
				'Nauru',
				'Australian Dollar',
				'AUD',
				36,
				'A$',
				'100 cents'
			),
			array(
				'Nepal',
				'Nepalese Rupee',
				'NPR',
				524,
				'NRs',
				'100 paise'
			),
			array(
				'Netherlands',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Netherlands Antilles',
				'Antillian Guilder',
				'ANG',
				532,
				'NAf.',
				'100 cents'
			),
			array(
				'New Caledonia',
				'CFP Franc',
				'XPF',
				953,
				'CFPF',
				'100 centimes'
			),
			array(
				'New Zealand',
				'New Zealand Dollar',
				'NZD',
				554,
				'NZ$',
				'100 cents'
			),
			array(
				'Nicaragua',
				'Cordoba Oro',
				'NIO',
				558,
				'C$',
				'100 centavos'
			),
			array(
				'Niger',
				'CFA Franc BCEAO',
				'XOF',
				952,
				'CFAF',
				'100 centimes'
			),
			array(
				'Nigeria',
				'Naira',
				'NGN',
				566,
				'&#8358;',
				'100 kobo'
			),
			array(
				'Niue',
				'New Zealand Dollar',
				'NZD',
				554,
				'NZ$',
				'100 cents'
			),
			array(
				'Norfolk Island',
				'Australian Dollar',
				'AUD',
				36,
				'A$',
				'100 cents'
			),
			array(
				'Northern Mariana Islands',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Norway',
				'Norwegian Krone',
				'NOK',
				578,
				'NKr',
				'100 &oslash;re'
			),
			array(
				'Oman',
				'Rial Omani',
				'OMR',
				512,
				'RO',
				'1000 baizas'
			),
			array(
				'Pakistan',
				'Pakistan Rupee',
				'PKR',
				586,
				'Rs',
				'100 paisa'
			),
			array(
				'Palau',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Panama',
				'Balboa',
				'PAB',
				590,
				'B',
				'100 centesimos'
			),
			array(
				'Panama',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Papua New Guinea',
				'Kina',
				'PGK',
				598,
				'K',
				'100 toeas'
			),
			array(
				'Paraguay',
				'Guarani',
				'PYG',
				600,
				'&#8370;', // slashed G
				'100 centimos'
			),
			array(
				'Peru',
				'Nuevo Sol',
				'PEN',
				604,
				'S/.',
				'100 centimos'
			),
			array(
				'Philippines',
				'Philippine Peso',
				'PHP',
				608,
				'&#8369;',
				'100 centavos'
			),
			array(
				'Pitcairn',
				'New Zealand Dollar',
				'NZD',
				554,
				'NZ$',
				'100 cents'
			),
			array(
				'Poland',
				'Zloty',
				'PLN',
				985,
				'z&#322;',
				'100 groszy'
			),
			array(
				'Portugal',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Puerto Rico',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Qatar',
				'Qatari Rial',
				'QAR',
				634,
				'QR',
				'100 dirhams'
			),
			array(
				'Reunion',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Romania',
				'Leu',
				'ROL',
				642,
				'L',
				'100 bani'
			),
			array(
				'Russian Federation',
				'Russian Ruble',
				'RUB',
				810,
				'R',
				'100 kopecks'
			),
			array(
				'Rwanda',
				'Rwanda Franc',
				'RWF',
				646,
				'RF',
				'100 centimes'
			),
			array(
				'St Helena',
				'St Helena Pound',
				'SHP',
				654,
				'&pound;S',
				'100 new pence'
			),
			array(
				'St Kitts - Nevis',
				'East Caribbean Dollar',
				'XCD',
				951,
				'EC$',
				'100 cents'
			),
			array(
				'Saint Lucia',
				'East Caribbean Dollar',
				'XCD',
				951,
				'EC$',
				'100 cents'
			),
			array(
				'St Pierre and Miquelon',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Saint Vincent and The Grenadines',
				'East Caribbean Dollar',
				'XCD',
				951,
				'EC$',
				'100 cents'
			),
			array(
				'Samoa',
				'Tala',
				'WST',
				882,
				'WS$',
				'100 sene'
			),
			array(
				'San Marino',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Sao Tome and Principe',
				'Dobra',
				'STD',
				678,
				'Db',
				'100 centimos'
			),
			array(
				'Saudi Arabia',
				'Saudi Riyal',
				'SAR',
				682,
				'SRls',
				'100 halalat'
			),
			array(
				'Senegal',
				'CFA Franc BCEAO',
				'XOF',
				952,
				'CFAF',
				'100 centimes'
			),
			array(
				'Serbia and Montenegro',
				'New Dinar',
				'YUM',
				891,
				'Din',
				'100 paras'
			),
			array(
				'Serbia and Montenegro',
				'Euro (in Montenegro)',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Seychelles',
				'Seychelles Rupee',
				'SCR',
				690,
				'SR',
				'100 cents'
			),
			array(
				'Sierra Leone',
				'Leone',
				'SLL',
				694,
				'Le',
				'100 cents'
			),
			array(
				'Singapore',
				'Singapore Dollar',
				'SGD',
				702,
				'S$',
				'100 cents'
			),
			array(
				'Slovakia',
				'Slovak Koruna',
				'SKK',
				703,
				'Sk',
				'100 haliers (halierov?)'
			),
			array(
				'Slovenia',
				'Tolar',
				'SIT',
				705,
				'SlT',
				'100 stotinov (stotins)'
			),
			array(
				'Solomon Islands',
				'Solomon Islands Dollar',
				'SBD',
				90,
				'SI$',
				'100 cents'
			),
			array(
				'Somalia',
				'Somali Shilling',
				'SOS',
				706,
				'So. Sh.',
				'100 centesimi'
			),
			array(
				'South Africa',
				'Rand',
				'ZAR',
				710,
				'R',
				'100 cents'
			),
			array(
				'Spain',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Sri Lanka',
				'Sri Lanka Rupee',
				'LKR',
				144,
				'SLRs',
				'100 cents'
			),
			array(
				'Sudan',
				'Sudanese Dinar',
				'SDD',
				736,
				'',
				'100 piastres'
			),
			array(
				'Suriname',
				'Surinam Guilder',
				'SRG',
				740,
				'Sf.',
				'100 cents'
			),
			array(
				'Svalbard and Jan Mayen Islands',
				'Norwegian Krone',
				'NOK',
				578,
				'NKr',
				'100 &oslash;re'
			),
			array(
				'Swaziland',
				'Lilangeni',
				'SZL',
				748,
				'L, pl., E',
				'100 cents'
			),
			array(
				'Sweden',
				'Swedish Krona',
				'SEK',
				752,
				'Sk',
				'100 &ouml;re'
			),
			array(
				'Switzerland',
				'Swiss Franc',
				'CHF',
				756,
				'SwF',
				'100 rappen/centimes'
			),
			array(
				'Syrian Arab Republic',
				'Syrian Pound',
				'SYP',
				760,
				'&pound;S',
				'100 piasters'
			),
			array(
				'Taiwan, Province Of China',
				'New Taiwan Dollar',
				'TWD',
				901,
				'NT$',
				'100 cents'
			),
			array(
				'Tajikistan',
				'Somoni',
				'TJS',
				972,
				'',
				'100 dirams'
			),
			array(
				'Tanzania, United Republic Of',
				'Tanzanian Shilling',
				'TZS',
				834,
				'TSh',
				'100 cents'
			),
			array(
				'Thailand',
				'Baht',
				'THB',
				764,
				'Bt',
				'100 stang'
			),
			array(
				'Togo',
				'CFA Franc BCEAO',
				'XOF',
				952,
				'CFAF',
				'100 centimes'
			),
			array(
				'Tokelau',
				'New Zealand Dollar',
				'NZD',
				554,
				'NZ$',
				'100 cents'
			),
			array(
				'Tonga',
				'Pa\'anga',
				'TOP',
				776,
				'T$',
				'100 seniti'
			),
			array(
				'Trinidad and Tobago',
				'Trinidad and Tobago Dollar',
				'TTD',
				780,
				'TT$',
				'100 cents'
			),
			array(
				'Tunisia',
				'Tunisian Dinar',
				'TND',
				788,
				'TD',
				'1000 millimes'
			),
			array(
				'Turkey',
				'Turkish Lira',
				'TRL',
				792,
				'TL',
				'100 kurus'
			),
			array(
				'Turkmenistan',
				'Manat',
				'TMM',
				795,
				'',
				'100 tenga'
			),
			array(
				'Turks and Caicos Islands',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Tuvalu',
				'Australian Dollar',
				'AUD',
				36,
				'A$',
				'100 cents'
			),
			array(
				'Uganda',
				'Uganda Shilling',
				'UGX',
				800,
				'USh',
				'100 cents'
			),
			array(
				'Ukraine',
				'Hryvnia',
				'UAH',
				980,
				'',
				'100 kopiykas'
			),
			array(
				'United Arab Emirates',
				'UAE Dirham',
				'AED',
				784,
				'Dh',
				'100 fils'
			),
			array(
				'United Kingdom',
				'Pound Sterling',
				'GBP',
				826,
				'&pound;',
				'100 pence'
			),
			array(
				'United States',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			// 			array(
			// 				'United States',
			// 				'(Same day)',
			// 				'USS',
			// 				998,
			// 				'$',
			// 				'100 cents'
			// 			),
			// 			array(
			// 				'United States',
			// 				'(Next day)',
			// 				'USN',
			// 				997,
			// 				'$',
			// 				'100 cents'
			// 			),
			array(
				'United States Minor Outlaying Islands',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Uruguay',
				'Peso Uruguayo',
				'UYU',
				858,
				'$U',
				'100 cent&eacute;simos'
			),
			array(
				'Uzbekistan',
				'Uzbekistan Sum',
				'UZS',
				860,
				'',
				'100 tiyin'
			),
			array(
				'Vanuatu',
				'Vatu',
				'VUV',
				548,
				'VT',
				'100 centimes'
			),
			array(
				'Vatican',
				'Euro',
				'EUR',
				978,
				'&euro;',
				'100 euro-cents'
			),
			array(
				'Venezuela',
				'Bolivar',
				'VEB',
				862,
				'Bs',
				'100 centimos'
			),
			array(
				'Vietnam',
				'Dong',
				'VND',
				704,
				'D',
				'10 hao or 100 xu'
			),
			array(
				'Virgin Islands (British)',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Virgin Islands (U.S.)',
				'US Dollar',
				'USD',
				840,
				'$',
				'100 cents'
			),
			array(
				'Wallis and Futuna Islands',
				'CFP Franc',
				'XPF',
				953,
				'CFPF',
				'100 centimes'
			),
			array(
				'Western Sahara',
				'Moroccan Dirham',
				'MAD',
				504,
				'DH',
				'100 centimes'
			),
			array(
				'Yemen',
				'Yemeni Rial',
				'YER',
				886,
				'YRls',
				'100 fils'
			),
			array(
				'Zambia',
				'Kwacha',
				'ZMK',
				894,
				'ZK',
				'100 ngwee'
			),
			array(
				'Zimbabwe',
				'Zimbabwe Dollar',
				'ZWD',
				716,
				'Z$',
				'100 cents'
			),
			array(
				'',
				'Gold Bond Markets Units',
				'XAU',
				959,
				'',
				''
			),
			array(
				'',
				'European Composite Unit (EURCO)',
				'XBA',
				955,
				'',
				''
			),
			array(
				'',
				'European Monetary Unit (E.M.U.-6)',
				'XBB',
				956,
				'',
				''
			),
			array(
				'',
				'European Unit of Account 9 (E.U.A.- 9)',
				'XBC',
				957,
				'',
				''
			),
			array(
				'',
				'European Unit of Account 17 (E.U.A.- 17)',
				'XBD',
				958,
				'',
				''
			),
			array(
				'',
				'Palladium',
				'XPD',
				964,
				'',
				''
			),
			array(
				'',
				'Platinum',
				'XPT',
				962,
				'',
				''
			),
			array(
				'',
				'Silver',
				'XAG',
				961,
				'',
				''
			),
			array(
				'',
				'UIC-Franc',
				'XFU',
				0,
				'',
				''
			),
			array(
				'',
				'Gold-Franc',
				'XFO',
				0,
				'',
				''
			),
			array(
				'',
				'Codes specifically reserved for testing purposes',
				'XTS',
				963,
				'',
				''
			),
			array(
				'',
				'No currency involved',
				'XXX',
				999,
				'',
				''
			)
		);
	}
}

