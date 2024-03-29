<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage model
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\World\Bootstrap;

use zesk\Application;
use zesk\ArrayTools;
use zesk\File;
use zesk\Hookable;
use zesk\JSON;

/**
 * @see Currency
 * @author kent
 *
 */
class BootstrapCurrency extends Hookable {
	/**
	 *
	 * @var array
	 */
	private array $include_currency = [];

	/**
	 *
	 * @var array
	 */
	private array $include_country = [];

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, array $options = []): self {
		return new self($application, $options);
	}

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 *
	 * @see Module::OPTION_INCLUDE_CURRENCY
	 * @see Module::OPTION_INCLUDE_COUNTRY
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration(Module::class);
		$include_currency = $this->option(Module::OPTION_INCLUDE_CURRENCY);
		if ($include_currency) {
			$this->include_currency = array_change_key_case(ArrayTools::keysFromValues(toList($include_currency), true));
		}
		$include_country = $this->option(Module::OPTION_INCLUDE_COUNTRY);
		if ($include_country) {
			$this->include_country = array_change_key_case(ArrayTools::keysFromValues(toList($include_country), true));
		}
	}

	public function bootstrap(): void {
		$x = $this->application->ormFactory(Currency::class);

		if ($this->optionBool('drop')) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}
		$codes = $this->_codes();
		foreach ($codes as $row) {
			$this->processRow($row);
		}
	}

	private function isIncluded(Currency $currency) {
		if ($this->include_country) {
			if ($currency->memberIsEmpty('bank_country')) {
				return false;
			}
			return $this->include_country[strtolower($currency->bank_country->code)] ?? false;
		}
		if ($this->include_currency) {
			$ccode = strtolower($currency->code);
			return $this->include_currency[$ccode] ?? false;
		}
		return true;
	}

	/**
	 * @param string $code
	 * @param string $name
	 * @return null|Country
	 */
	private function determineCountry(string $code, string $name): null|Country {
		if (empty($name)) {
			return null;
		}
		if (str_starts_with($name, '*')) {
			return null;
		}
		$country = $this->application->ormFactory(Country::class, [
			Country::MEMBER_CODE => $code, Country::MEMBER_NAME => $name,
		]);

		try {
			$result = $country->find(Country::MEMBER_CODE);
		} catch (ORMNotFound|ORMEmpty) {
			try {
				$result = $country->find([
					Country::MEMBER_NAME . '|LIKE' => $name,
				]);
			} catch (ORMNotFound|ORMEmpty) {
				return null;
			}
		}
		/** @var Country $result */
		return $result;
	}

	private function processRow(array $row): void {
		$countryName = $row[0];
		$name = $row[1];
		$codeName = $row[2];
		$codeNumber = $row[3];
		$symbol = $row[4];
		$fractional_string = $row[5];
		$code = strtolower(substr($codeName, 0, 2));

		$country = $this->determineCountry($code, $countryName);
		if (!$country && !$this->optionBool('include_no_country')) {
			return;
		}
		$fields['bank_country'] = $country;
		if (empty($symbol)) {
			$symbol = $codeName;
		}

		$fields['id'] = $id = intval($codeNumber);
		$fields['name'] = $name;
		$fields['code'] = $codeName;
		$fields['symbol'] = $symbol;
		[$fractional, $units] = pair($fractional_string, ' ');
		$fields['fractional'] = intval($fractional);
		$fields['fractional_units'] = $units;
		$fields['format'] = '{symbol}{amount}';

		if (empty($id)) {
			$this->application->debug('Unknown id for currency {code} {name}', $fields);
			return;
		}

		$currency = $this->application->ormFactory(Currency::class);
		/* @var $currency Currency */
		if ($this->isIncluded($currency)) {
			if ($this->optionBool('delete_mismatched_ids')) {
				$found = $currency->find();
				if ($found->id() !== $id) {
					$currency->delete();
					$currency->setMembers($fields)->store();
				} else {
					$currency->store();
				}
			} else {
				$currency->find();
				$currency->setMembers($fields)->store();
			}
		}
	}

	private function _codes(): array {
		$codes = $this->_somewhat_dated_codes();
		$valid_ones = $this->_valid_codes();
		$missing_ones = array_change_key_case(ArrayTools::valuesFlipCopy($valid_ones));
		foreach ($codes as $index => $row) {
			$code = strtolower($row[2]);
			if (!isset($missing_ones[$code])) {
				if ($this->optionBool('debug')) {
					$this->application->debug('Code {2} ({1}) no longer valid, remove it', $row);
				}
				unset($codes[$index]);

				continue;
			}
			unset($missing_ones[$code]);
		}
		if (count($missing_ones) > 0) {
			$this->application->debug('Currency codes {missing_ones} need to be added', [
				'missing_ones' => $missing_ones,
			]);
		}
		return $codes;
	}

	/**
	 * https://gist.githubusercontent.com/Fluidbyte/2973986/raw/b0d1722b04b0a737aade2ce6e055263625a0b435/Common-Currency.json
	 */
	public function _somewhat_recent_codes(): array {
		$file = $this->application->modules->path('world', 'bootstrap-data/currency.json');
		File::depends($file);
		return JSON::decode(file_get_contents($file));
	}

	public static function _process_somewhat_dated_codes(): array {
		$codes = self::_somewhat_dated_codes();
		$result = [];
		foreach ($codes as $row) {
			$item = ArrayTools::keysMap($row, [
				'country_name', 'name', 'code', 'id', 'symbol', 'unit_phrase',
			]);

			$result[$item['code']] = $item;
		}
		return $result;
	}

	public static function _somewhat_dated_codes(): array {
		return [
			[
				'Afghanistan', 'Afghani', 'AFA', 4, 'Af', '100 puls',
			], [
				'Albania', 'Lek', 'ALL', 8, 'Lek', '100 qindarkë',
			], [
				'Algeria', 'Algerian Dinar', 'DZD', 12, 'DA', '100 centimes',
			], [
				'American Samoa', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Andorra', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Angola', 'New Kwanza', 'AON', 24, 'Kz', '100 lwei',
			], [
				'Anguilla', 'East Caribbean Dollar', 'XCD', 951, 'EC$', '100 cents',
			], [
				'Antarctica', 'No universal currency', '', '', '', '',
			], [
				'Antigua and Barbuda', 'East Caribbean Dollar', 'XCD', 951, 'EC$', '100 cents',
			], [
				'Argentina', 'Argentine Peso', 'ARS', 32, '$', '100 centavos',
			], [
				'Armenia', 'Armenian Dram', 'AMD', 51, '', '100 luma',
			], [
				'Aruba', 'Aruban Guilder', 'AWG', 533, 'Af.', '100 cents',
			], [
				'Australia', 'Australian Dollar', 'AUD', 36, 'A$', '100 cents',
			], [
				'Austria', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Azerbaijan', 'Azerbaijanian Manat', 'AZM', 31, '', '100 gopik',
			], [
				'Bahamas', 'Bahamian Dollar', 'BSD', 44, 'B$', '100 cents',
			], [
				'Bahrain', 'Bahraini Dinar', 'BHD', 48, 'BD', '1000 fils',
			], [
				'Bangladesh', 'Taka', 'BDT', 50, 'Tk', '100 poisha',
			], [
				'Barbados', 'Barbados Dollar', 'BBD', 52, 'Bds$', '100 cents',
			], [
				'Belarus', 'Belarussian Ruble', 'BYR', 974, 'BR', '',
			], [
				'Belgium', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Belize', 'Belize Dollar', 'BZD', 84, 'BZ$', '100 cents',
			], [
				'Benin', 'CFA Franc BCEAO', 'XOF', 952, 'CFAF', '100 centimes',
			], [
				'Bermuda', 'Bermudian Dollar', 'BMD', 60, 'Bd$', '100 cents',
			], [
				'Bhutan', 'Ngultrum', 'BTN', 64, 'Nu', '100 chetrum',
			], [
				'Bhutan', 'Indian Rupee', 'INR', 356, 'Rs', '100 paise',
			], [
				'Bosnia and Herzegovina', 'Convertible Marks', 'BAM', 977, 'KM', '100 fennig',
			], [
				'Botswana', 'Pula', 'BWP', 72, 'P', '100 thebe',
			], [
				'Bouvet Island', 'Norwegian Krone', 'NOK', 578, 'NKr', '100 &oslash;re',
			], [
				'Brazil', 'Brazilian Real', 'BRL', 986, 'R$', '100 centavos',
			], [
				'British Indian Ocean Territory', 'Pound Sterling', 'GBP', 826, '&pound;', '100 pence',
			], [
				'British Indian Ocean Territory', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Brunei Darussalam', 'Brunei Dollar', 'BND', 96, 'B$', '100 sen',
			], [
				'Bulgaria', 'Lev', 'BGL', 100, 'Lv', '100 stotinki',
			], [
				'Burkina Faso', 'CFA Franc BCEAO', 'XOF', 952, 'CFAF', '100 centimes',
			], [
				'Burundi', 'Burundi Franc', 'BIF', 108, 'FBu', '100 centimes',
			], [
				'Cambodia', 'Riel', 'KHR', 116, 'CR', '100 sen',
			], [
				'Cameroon', 'CFA Franc BEAC', 'XAF', 950, 'CFAF', '100 centimes',
			], [
				'Canada', 'Canadian Dollar', 'CAD', 124, 'Can$', '100 cents',
			], [
				'Cape Verde', 'Cape Verde Escudo', 'CVE', 132, 'C.V.Esc.', '100 centavos',
			], [
				'Cayman Islands', 'Cayman Islands Dollar', 'KYD', 136, 'CI$', '100 cents',
			], [
				'Central African Republic', 'CFA Franc BEAC', 'XAF', 950, 'CFAF', '100 centimes',
			], [
				'Chad', 'CFA Franc BEAC', 'XAF', 950, 'CFAF', '100 centimes',
			], [
				'Chile', 'Chilean Peso', 'CLP', 152, 'Ch$', '100 centavos',
			], [
				'China', 'Yuan Renminbi', 'CNY', 156, 'Y', '10 jiao',
			], [
				'Christmas Island', 'Australian Dollar', 'AUD', 36, 'A$', '100 cents',
			], [
				'Cocos (Keeling) Islands', 'Australian Dollar', 'AUD', 36, 'A$', '100 cents',
			], [
				'Colombia', 'Colombian Peso', 'COP', 170, 'Col$', '100 centavos',
			], [
				'Comoros', 'Comoro Franc', 'KMF', 174, 'CF', '',
			], [
				'Republic of the Congo', 'CFA Franc BEAC', 'XAF', 950, 'CFAF', '100 centimes',
			], [
				'Democratic Republic of the Congo', 'Franc Congolais', 'CDF', 976, '', '100 centimes',
			], [
				'Cook Islands', 'New Zealand Dollar', 'NZD', 554, 'NZ$', '100 cents',
			], [
				'Costa Rica', 'Costa Rican Colon', 'CRC', 188, '&#8353;', '100 centimos',
			], [
				'Ivory Coast', 'CFA Franc BCEAO', 'XOF', 952, 'CFAF', '100 centimes',
			], [
				'Croatia', 'Kuna', 'HRK', 191, 'HRK', '100 lipas',
			], [
				'Cuba', 'Cuban Peso', 'CUP', 192, 'Cu$', '100 centavos',
			], [
				'Cyprus', 'Cyprus Pound', 'CYP', 196, '&pound;C', '100 cents',
			], [
				'Cyprus - TRNC', 'Turkish Lira', 'TRL', 792, 'TL', '100 kurus',
			], [
				'Czech Republic', 'Czech Koruna', 'CZK', 203, 'K&#269;', // Kc (with hacek o)
				'100 haleru',
			], [
				'Denmark', 'Danish Krone', 'DKK', 208, 'Dkr', '100 &oslash;re',
			], [
				'Djibouti', 'Djibouti Franc', 'DJF', 262, 'DF', '100 centimes',
			], [
				'Dominica', 'East Caribbean Dollar', 'XCD', 951, 'EC$', '100 cents',
			], [
				'Dominican Republic', 'Dominican Peso', 'DOP', 214, 'RD$', '100 centavos',
			], [
				'East Timor', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Ecuador', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Egypt', 'Egyptian Pound', 'EGP', 818, '&pound;E', '100 piasters or 1000 milliemes',
			], [
				'El Salvador', 'El Salvador Colon', 'SVC', 222, '&cent;', '100 centavos',
			], [
				'Equatorial Guinea', 'CFA Franc BEAC', 'XAF', 950, 'CFAF', '100 centimos',
			], [
				'Estonia', 'Kroon', 'EEK', 233, 'Nfa', '100 cents',
			], [
				'Eritrea', 'Nakfa', 'ERN', 232, 'KR', '100 senti',
			], [
				'Ethiopia', 'Ethiopian Birr', 'ETB', 230, 'Br', '100 cents',
			], [
				'*European Union', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Faeroe Islands', 'Danish Krone', 'DKK', 208, 'Dkr', '100 &oslash;re',
			], [
				'Falkland Islands (Malvinas)', 'Pound', 'FKP', 238, '&pound;F', '100 pence',
			], [
				'Fiji', 'Fiji Dollar', 'FJD', 242, 'F$', '100 cents',
			], [
				'Finland', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'France', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'French Guiana', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'French Polynesia', 'CFP Franc', 'XPF', 953, 'CFPF', '100 centimes',
			], [
				'French Southern Territories', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Gabon', 'CFA Franc BEAC', 'XAF', 950, 'CFAF', '100 centimes',
			], [
				'Gambia', 'Dalasi', 'GMD', 270, 'D', '100 butut',
			], [
				'Georgia', 'Lari', 'GEL', 981, '', '100 tetri',
			], [
				'Germany', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Ghana', 'Cedi', 'GHC', 288, '&cent;', '100 psewas',
			], [
				'Gibraltar', 'Gibraltar Pound', 'GIP', 292, '&pound;G', '100 pence',
			], [
				'Greece', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Greenland', 'Danish Krone', 'DKK', 208, 'Dkr', '100 &oslash;re',
			], [
				'Grenada', 'East Caribbean Dollar', 'XCD', 951, 'EC$', '100 cents',
			], [
				'Guadeloupe', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Guam', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Guatemala', 'Quetzal', 'GTQ', 320, 'Q', '100 centavos',
			], [
				'Guinea', 'Guinea Franc', 'GNF', 324, '', '',
			], [
				'Guinea-Bissau', 'CFA Franc BCEAO', 'XOF', 952, 'CFAF', '100 centimes',
			], [
				'Guyana', 'Guyana Dollar', 'GYD', 328, 'G$', '100 cents',
			], [
				'Haiti', 'Gourde', 'HTG', 332, 'G', '100 centimes',
			], [
				'Haiti', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Heard and Mcdonald Islands', 'Australian Dollar', 'AUD', 36, 'A$', '100 cents',
			], [
				'Honduras', 'Lempira', 'HNL', 340, 'L', '100 centavos',
			], [
				'Hong Kong', 'Hong Kong Dollar', 'HKD', 344, 'HK$', '100 cents',
			], [
				'Hungary', 'Forint', 'HUF', 348, 'Ft', '-none-',
			], [
				'Iceland', 'Iceland Krona', 'ISK', 352, 'IKr', '100 aurar',
			], [
				'India', 'Indian Rupee', 'INR', 356, 'Rs', '100 paise',
			], [
				'Indonesia', 'Rupiah', 'IDR', 360, 'Rp', '',
			], // 			array(
			// 				'International Monetary Fund (Imf)',
			// 				'SDR',
			// 				'XDR',
			// 				960,
			// 				'SDR',
			// 				''
			// 			),
			[
				'Iran (Islamic Republic Of)', 'Iranian Rial', 'IRR', 364, 'Rls', '100 Dinar',
			], [
				'Iraq', 'Iraqi Dinar', 'IQD', 368, 'ID', '1000 fils',
			], [
				'Ireland', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Israel', 'New Israeli Sheqel', 'ILS', 376, 'NIS', '100 new agorot',
			], [
				'Italy', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Jamaica', 'Jamaican Dollar', 'JMD', 388, 'J$', '100 cents',
			], [
				'Japan', 'Yen', 'JPY', 392, '&yen;', '100 sen',
			], [
				'Jersey', 'Pound Sterling', 'GBP', 826, '&pound;', '100 pence',
			], [
				'Jordan', 'Jordanian Dinar', 'JOD', 400, 'JD', '1000 fils',
			], [
				'Kazakhstan', 'Tenge', 'KZT', 398, '', '100 tiyn',
			], [
				'Kenya', 'Kenyan Shilling', 'KES', 404, 'K Sh', '100 cents',
			], [
				'Kiribati', 'Australian Dollar', 'AUD', 36, 'A$', '100 cents',
			], [
				'Korea, Democratic People\'s Republic Of', 'North Korean Won', 'KPW', 408, 'Wn', '100 chon',
			], [
				'Korea, Republic Of', 'Won', 'KRW', 410, 'W', '100 chon',
			], [
				'Kuwait', 'Kuwaiti Dinar', 'KWD', 414, 'KD', '1000 fils',
			], [
				'Kyrgyzstan', 'Som', 'KGS', 417, '', '100 tyyn',
			], [
				'Lao People\'s Democratic Republic', 'Kip', 'LAK', 418, 'KN', '100 at',
			], [
				'Latvia', 'Latvian Lats', 'LVL', 428, 'Ls', '100 santims',
			], [
				'Lebanon', 'Lebanese Pound', 'LBP', 422, 'L.L.', '100 piastres',
			], [
				'Lesotho', 'Loti', 'LSL', 426, 'L, pl., M', '100 lisente',
			], [
				'Lesotho', 'Rand', 'ZAR', 710, 'R', '100 cents',
			], [
				'Liberia', 'Liberian Dollar', 'LRD', 430, '$', '100 cents',
			], [
				'Libyan Arab Jamahiriya', 'Libyan Dinar', 'LYD', 434, 'LD', '1000 dirhams',
			], [
				'Liechtenstein', 'Swiss Franc', 'CHF', 756, 'SwF', '100 rappen/centimes',
			], [
				'Lithuania', 'Lithuanian Litas', 'LTL', 440, '', '100 centu',
			], [
				'Luxembourg', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Macau', 'Pataca', 'MOP', 446, 'P', '100 avos',
			], [
				'Macedonia, The Former Yugoslav Republic Of', 'Denar', 'MKD', 807, 'MKD', '100 deni',
			], [
				'Madagascar', 'Malagasy Ariary', 'MGA', 450, 'FMG', '5 Iraimbilanja',
			], [
				'Malawi', 'Kwacha', 'MWK', 454, 'MK', '100 tambala',
			], [
				'Malaysia', 'Malaysian Ringgit', 'MYR', 458, 'RM', '100 sen',
			], [
				'Maldives', 'Rufiyaa', 'MVR', 462, 'Rf', '100 lari',
			], [
				'Mali', 'CFA Franc BCEAO', 'XOF', 952, 'CFAF', '100 centimes',
			], [
				'Malta', 'Maltese Lira', 'MTL', 470, 'Lm', '100 cents',
			], [
				'Marshall Islands', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Martinique', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Mauritania', 'Ouguiya', 'MRO', 478, 'UM', '5 khoums',
			], [
				'Mauritius', 'Mauritius Rupee', 'MUR', 480, 'Mau Rs', '100 cents',
			], [
				'Mexico', 'Mexican Peso', 'MXN', 484, 'Mex$', '100 centavos',
			], [
				'Micronesia', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Moldova, Republic Of', 'Moldovan Leu', 'MDL', 498, '', '',
			], [
				'Monaco', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Mongolia', 'Tugrik', 'MNT', 496, 'Tug', '100 mongos',
			], [
				'Montserrat', 'East Caribbean Dollar', 'XCD', 951, 'EC$', '100 cents',
			], [
				'Morocco', 'Moroccan Dirham', 'MAD', 504, 'DH', '100 centimes',
			], [
				'Mozambique', 'Metical', 'MZM', 508, 'Mt', '100 centavos',
			], [
				'Myanmar', 'Kyat', 'MMK', 104, 'K', '100 pyas',
			], [
				'Namibia', 'Namibia Dollar', 'NAD', 516, 'N$', '100 cents',
			], [
				'Namibia', 'Rand', 'ZAR', 710, 'R', '100 cents',
			], [
				'Nauru', 'Australian Dollar', 'AUD', 36, 'A$', '100 cents',
			], [
				'Nepal', 'Nepalese Rupee', 'NPR', 524, 'NRs', '100 paise',
			], [
				'Netherlands', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Netherlands Antilles', 'Antillian Guilder', 'ANG', 532, 'NAf.', '100 cents',
			], [
				'New Caledonia', 'CFP Franc', 'XPF', 953, 'CFPF', '100 centimes',
			], [
				'New Zealand', 'New Zealand Dollar', 'NZD', 554, 'NZ$', '100 cents',
			], [
				'Nicaragua', 'Cordoba Oro', 'NIO', 558, 'C$', '100 centavos',
			], [
				'Niger', 'CFA Franc BCEAO', 'XOF', 952, 'CFAF', '100 centimes',
			], [
				'Nigeria', 'Naira', 'NGN', 566, '&#8358;', '100 kobo',
			], [
				'Niue', 'New Zealand Dollar', 'NZD', 554, 'NZ$', '100 cents',
			], [
				'Norfolk Island', 'Australian Dollar', 'AUD', 36, 'A$', '100 cents',
			], [
				'Northern Mariana Islands', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Norway', 'Norwegian Krone', 'NOK', 578, 'NKr', '100 &oslash;re',
			], [
				'Oman', 'Rial Omani', 'OMR', 512, 'RO', '1000 baizas',
			], [
				'Pakistan', 'Pakistan Rupee', 'PKR', 586, 'Rs', '100 paisa',
			], [
				'Palau', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Panama', 'Balboa', 'PAB', 590, 'B', '100 centesimos',
			], [
				'Panama', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Papua New Guinea', 'Kina', 'PGK', 598, 'K', '100 toeas',
			], [
				'Paraguay', 'Guarani', 'PYG', 600, '&#8370;', // slashed G
				'100 centimos',
			], [
				'Peru', 'Nuevo Sol', 'PEN', 604, 'S/.', '100 centimos',
			], [
				'Philippines', 'Philippine Peso', 'PHP', 608, '&#8369;', '100 centavos',
			], [
				'Pitcairn', 'New Zealand Dollar', 'NZD', 554, 'NZ$', '100 cents',
			], [
				'Poland', 'Zloty', 'PLN', 985, 'z&#322;', '100 groszy',
			], [
				'Portugal', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Puerto Rico', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Qatar', 'Qatari Rial', 'QAR', 634, 'QR', '100 dirhams',
			], [
				'Reunion', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Romania', 'Leu', 'ROL', 642, 'L', '100 bani',
			], [
				'Russian Federation', 'Russian Ruble', 'RUB', 810, 'R', '100 kopecks',
			], [
				'Rwanda', 'Rwanda Franc', 'RWF', 646, 'RF', '100 centimes',
			], [
				'St Helena', 'St Helena Pound', 'SHP', 654, '&pound;S', '100 new pence',
			], [
				'%Kitts%', 'East Caribbean Dollar', 'XCD', 951, 'EC$', '100 cents',
			], [
				'Saint Lucia', 'East Caribbean Dollar', 'XCD', 951, 'EC$', '100 cents',
			], [
				'%Saint Pierre%', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Saint Vincent and The Grenadines', 'East Caribbean Dollar', 'XCD', 951, 'EC$', '100 cents',
			], [
				'Samoa', 'Tala', 'WST', 882, 'WS$', '100 sene',
			], [
				'San Marino', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Sao Tome and Principe', 'Dobra', 'STD', 678, 'Db', '100 centimos',
			], [
				'Saudi Arabia', 'Saudi Riyal', 'SAR', 682, 'SRls', '100 halalat',
			], [
				'Senegal', 'CFA Franc BCEAO', 'XOF', 952, 'CFAF', '100 centimes',
			], [
				'Serbia and Montenegro', 'New Dinar', 'YUM', 891, 'Din', '100 paras',
			], [
				'Serbia and Montenegro', 'Euro (in Montenegro)', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Seychelles', 'Seychelles Rupee', 'SCR', 690, 'SR', '100 cents',
			], [
				'Sierra Leone', 'Leone', 'SLL', 694, 'Le', '100 cents',
			], [
				'Singapore', 'Singapore Dollar', 'SGD', 702, 'S$', '100 cents',
			], [
				'Slovakia', 'Euro', 'EUR', 703, '&euro;', '100 euro-cents',
			], [
				'Slovenia', 'Euro', 'EUR', 705, '&euro;', '100 euro-cents',
			], [
				'Solomon Islands', 'Solomon Islands Dollar', 'SBD', 90, 'SI$', '100 cents',
			], [
				'Somalia', 'Somali Shilling', 'SOS', 706, 'So. Sh.', '100 centesimi',
			], [
				'South Africa', 'Rand', 'ZAR', 710, 'R', '100 cents',
			], [
				'Spain', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Sri Lanka', 'Sri Lanka Rupee', 'LKR', 144, 'SLRs', '100 cents',
			], [
				'Sudan', 'Sudanese Dinar', 'SDD', 736, '', '100 piastres',
			], [
				'Suriname', 'Surinam Guilder', 'SRG', 740, 'Sf.', '100 cents',
			], [
				'Svalbard and Jan Mayen Islands', 'Norwegian Krone', 'NOK', 578, 'NKr', '100 &oslash;re',
			], [
				'Swaziland', 'Lilangeni', 'SZL', 748, 'L, pl., E', '100 cents',
			], [
				'Sweden', 'Swedish Krona', 'SEK', 752, 'Sk', '100 &ouml;re',
			], [
				'Switzerland', 'Swiss Franc', 'CHF', 756, 'SwF', '100 rappen/centimes',
			], [
				'Syrian Arab Republic', 'Syrian Pound', 'SYP', 760, '&pound;S', '100 piasters',
			], [
				'Taiwan, Province Of China', 'New Taiwan Dollar', 'TWD', 901, 'NT$', '100 cents',
			], [
				'Tajikistan', 'Somoni', 'TJS', 972, '', '100 dirams',
			], [
				'Tanzania, United Republic Of', 'Tanzanian Shilling', 'TZS', 834, 'TSh', '100 cents',
			], [
				'Thailand', 'Baht', 'THB', 764, 'Bt', '100 stang',
			], [
				'Togo', 'CFA Franc BCEAO', 'XOF', 952, 'CFAF', '100 centimes',
			], [
				'Tokelau', 'New Zealand Dollar', 'NZD', 554, 'NZ$', '100 cents',
			], [
				'Tonga', 'Pa\'anga', 'TOP', 776, 'T$', '100 seniti',
			], [
				'Trinidad and Tobago', 'Trinidad and Tobago Dollar', 'TTD', 780, 'TT$', '100 cents',
			], [
				'Tunisia', 'Tunisian Dinar', 'TND', 788, 'TD', '1000 millimes',
			], [
				'Turkey', 'Turkish Lira', 'TRL', 792, 'TL', '100 kurus',
			], [
				'Turkmenistan', 'Manat', 'TMM', 795, '', '100 tenga',
			], [
				'Turks and Caicos Islands', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Tuvalu', 'Australian Dollar', 'AUD', 36, 'A$', '100 cents',
			], [
				'Uganda', 'Uganda Shilling', 'UGX', 800, 'USh', '100 cents',
			], [
				'Ukraine', 'Hryvnia', 'UAH', 980, '', '100 kopiykas',
			], [
				'United Arab Emirates', 'UAE Dirham', 'AED', 784, 'Dh', '100 fils',
			], [
				'United Kingdom', 'Pound Sterling', 'GBP', 826, '&pound;', '100 pence',
			], [
				'United States', 'US Dollar', 'USD', 840, '$', '100 cents',
			], // 			array(
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
			[
				'United States Minor Outlaying Islands', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Uruguay', 'Peso Uruguayo', 'UYU', 858, '$U', '100 cent&eacute;simos',
			], [
				'Uzbekistan', 'Uzbekistan Sum', 'UZS', 860, '', '100 tiyin',
			], [
				'Vanuatu', 'Vatu', 'VUV', 548, 'VT', '100 centimes',
			], [
				'Vatican', 'Euro', 'EUR', 978, '&euro;', '100 euro-cents',
			], [
				'Venezuela', 'Bolivar', 'VEB', 862, 'Bs', '100 centimos',
			], [
				'Vietnam', 'Dong', 'VND', 704, 'D', '10 hao or 100 xu',
			], [
				'Virgin Islands (British)', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'Virgin Islands (U.S.)', 'US Dollar', 'USD', 840, '$', '100 cents',
			], [
				'%Wallis%', 'CFP Franc', 'XPF', 953, 'CFPF', '100 centimes',
			], [
				'Western Sahara', 'Moroccan Dirham', 'MAD', 504, 'DH', '100 centimes',
			], [
				'Yemen', 'Yemeni Rial', 'YER', 886, 'YRls', '100 fils',
			], [
				'Zambia', 'Kwacha', 'ZMK', 894, 'ZK', '100 ngwee',
			], [
				'Zimbabwe', 'Zimbabwe Dollar', 'ZWD', 716, 'Z$', '100 cents',
			], [
				'', 'Gold Bond Markets Units', 'XAU', 959, '', '',
			], [
				'', 'European Composite Unit (EURCO)', 'XBA', 955, '', '',
			], [
				'', 'European Monetary Unit (E.M.U.-6)', 'XBB', 956, '', '',
			], [
				'', 'European Unit of Account 9 (E.U.A.- 9)', 'XBC', 957, '', '',
			], [
				'', 'European Unit of Account 17 (E.U.A.- 17)', 'XBD', 958, '', '',
			], [
				'', 'Palladium', 'XPD', 964, '', '',
			], [
				'', 'Platinum', 'XPT', 962, '', '',
			], [
				'', 'Silver', 'XAG', 961, '', '',
			], [
				'', 'UIC-Franc', 'XFU', 0, '', '',
			], [
				'', 'Gold-Franc', 'XFO', 0, '', '',
			], [
				'', 'Codes specifically reserved for testing purposes', 'XTS', 963, '', '',
			], [
				'', 'No currency involved', 'XXX', 999, '', '',
			],
		];
	}

	/**
	 * Taken from http://www.xe.com/currency/ HTML as of 2017-09-13
	 */
	private function _valid_codes(): array {
		return [
			'USD', 'EUR', 'GBP', 'INR', 'AUD', 'CAD', 'SGD', 'CHF', 'MYR', 'JPY', 'CNY', 'NZD', 'THB', 'HUF', 'AED',
			'HKD', 'MXN', 'ZAR', 'PHP', 'SEK', 'IDR', 'SAR', 'BRL', 'TRY', 'KES', 'KRW', 'EGP', 'IQD', 'NOK', 'KWD',
			'RUB', 'DKK', 'PKR', 'ILS', 'PLN', 'QAR', 'XAU', 'OMR', 'COP', 'CLP', 'TWD', 'ARS', 'CZK', 'VND', 'MAD',
			'JOD', 'BHD', 'XOF', 'LKR', 'UAH', 'NGN', 'TND', 'UGX', 'RON', 'BDT', 'PEN', 'GEL', 'XAF', 'FJD', 'VEF',
			'BYN', 'HRK', 'UZS', 'BGN', 'DZD', 'IRR', 'DOP', 'ISK', 'XAG', 'CRC', 'SYP', 'LYD', 'JMD', 'MUR', 'GHS',
			'AOA', 'UYU', 'AFN', 'LBP', 'XPF', 'TTD', 'TZS', 'ALL', 'XCD', 'GTQ', 'NPR', 'BOB', 'ZWD', 'BBD', 'CUC',
			'LAK', 'BND', 'BWP', 'HNL', 'PYG', 'ETB', 'NAD', 'PGK', 'SDG', 'MOP', 'NIO', 'BMD', 'KZT', 'PAB', 'BAM',
			'GYD', 'YER', 'MGA', 'KYD', 'MZN', 'RSD', 'SCR', 'AMD', 'SBD', 'AZN', 'SLL', 'TOP', 'BZD', 'MWK', 'GMD',
			'BIF', 'SOS', 'HTG', 'GNF', 'MVR', 'MNT', 'CDF', 'STD', 'TJS', 'KPW', 'MMK', 'LSL', 'LRD', 'KGS', 'GIP',
			'XPT', 'MDL', 'CUP', 'KHR', 'MKD', 'VUV', 'MRO', 'ANG', 'SZL', 'CVE', 'SRD', 'XPD', 'SVC', 'BSD', 'XDR',
			'RWF', 'AWG', 'DJF', 'BTN', 'KMF', 'WST', 'SPL', 'ERN', 'FKP', 'SHP', 'JEP', 'TMT', 'TVD', 'IMP', 'GGP',
			'ZMW',
		];
	}
}
