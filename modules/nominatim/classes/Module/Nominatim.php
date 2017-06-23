<?php

/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *        
 */
class Module_Nominatim extends Module {
	/**
	 *
	 * @var string
	 */
	private $url = null;
	/**
	 *
	 * @var integer
	 */
	private $request_delay = null;
	
	/**
	 * 
	 */
	public function hook_configured() {
		// Value is modified in hook_cron
		$this->application->configuration->deprecated("Module_Nominatim");
	}
	
	/**
	 * cron every minute
	 */
	public function hook_cron() {
		if (!$this->option_bool('force') && $this->application->development()) {
			return;
		}
		$settings = Settings::instance();
		$last_request_var = __CLASS__ . "::last_request";
		$settings->deprecated("Module_Nominatim::last_request", $last_request_var);
		
		$url = $this->url = $this->option('url_geocode');
		$rph = $this->option('geocode_requests_per_hour', 60);
		
		$this->request_delay = intval(3600 / $rph);
		
		if (!URL::valid($url)) {
			$this->application->logger->notice("{class}::cron - no URL_GEOCODE configured");
			return;
		}
		// Keep our database clean. Set lat/long to null when close to zero.
		$update = $this->application->query_update("zesk\\Contact_Address")->values(array(
			'latitude' => null,
			'longitude' => null,
			'geocoded' => null
		));
		$sql = $update->sql();
		$update->where(array(
			array(
				'*' . $sql->function_abs('latitude') . "|<=" => 0.00001,
				'*' . $sql->function_abs('longitude') . "|<=" => 0.00001
			),
			'latitude|!=' => null,
			'longitude|!=' => null
		));
		$update->execute();
		
		// Set geocoded date to created date when lat/long are set
		$update = $this->application->query_update("zesk\\Contact_Address")->values(array(
			'*geocoded' => 'created'
		))->where(array(
			array(
				'*' . $sql->function_abs('latitude') . "|>" => 0.00001,
				'*' . $sql->function_abs('longitude') . "|>" => 0.00001
			),
			'geocoded' => null
		));
		$update->execute();
		
		$query = $this->application->query_select('zesk\\Contact_Address')->where(array(
			array(
				'geocoded' => null,
				'geocoded|<=' => Timestamp::now()->add_unit(-abs($this->option_integer("geocode_refresh_days", 30)), Timestamp::UNIT_DAY)
			)
		));
		
		$http = new Net_HTTP_Client();
		$http->user_agent("Module_Nominatum in Zesk Library http://zesk.com/ v" . Version::release());
		/* @var $item Contact_Address */
		$run_time = $this->option("run_time", 60);
		$timer = new Timer();
		$items = $query->object_iterator();
		try {
			foreach ($items as $item) {
				$now = time();
				$last_request = $settings->get($last_request_var, null);
				$next_request = $last_request === null ? time() : $last_request + $this->request_delay;
				$wait_seconds = $next_request - $now;
				//$this->application->logger->Debug("last request was $last_request delay is " . $this->request_delay . " wait_seconds is $wait_seconds");
				if ($wait_seconds < 0) {
					$wait_seconds = 0;
				}
				if ($wait_seconds + $timer->elapsed() > $run_time) {
					$this->application->logger->debug("Need to wait {wait_seconds} before next request ... exiting.", array(
						'wait_seconds' => $wait_seconds
					));
					break;
				}
				if ($wait_seconds > 0) {
					$this->application->logger->debug("Waiting {wait_seconds} seconds to do geocoding", compact("wait_seconds"));
					sleep($wait_seconds);
				}
				$this->geocode_address($http, $item);
				$settings->set($last_request_var, time());
				if ($timer->elapsed() > $run_time) {
					$this->application->logger->debug("Ran out of time ...");
					break;
				}
			}
		} catch (Net_HTTP_Client_Exception $e) {
			$this->application->logger->error("Net_HTTP_Client_Exception {code} {message} {backtrace}", array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"backtrace" => $e->getTraceAsString()
			));
		}
	}
	
	/**
	 *
	 * @param Net_HTTP_Client $http        	
	 * @param Contact_Address $item        	
	 * @return boolean
	 */
	private function geocode_address(Net_HTTP_Client $http, Contact_Address $item) {
		$query = $this->option_array("url_geocode_query", array());
		$query['format'] = 'json';
		$query['q'] = implode(", ", arr::clean(array(
			$item->street,
			$item->additional,
			$item->city,
			$item->province,
			$item->postal_code,
			$item->country_code
		)));
		
		$alt_query = $query;
		$alt_query['street'] = trim("$item->street $item->additional");
		$alt_query['city'] = $item->city;
		$alt_query['county'] = $item->county;
		$alt_query['state'] = $item->province;
		$alt_query['country'] = $item->country_code;
		$alt_query['postalcode'] = $item->postal_code;
		$alt_query = arr::trim_clean($alt_query);
		
		$this_url = URL::query_append($this->url, $query);
		$http->url($this_url);
		$raw = $http->go();
		if ($http->response_code() === Net_HTTP::Status_OK) {
			$this->application->logger->debug("Geocode results for {url} is {data}", array(
				"url" => $this_url,
				"data" => $raw
			));
			$item->geocode_data = $result = JSON::decode($raw);
			$item->geocoded = Timestamp::now();
			if (count($result) > 0) {
				$result = first($result);
				$lat = avalue($result, 'lat');
				if ($lat) {
					$item->latitude = doubleval($lat);
				}
				$lon = avalue($result, 'lon');
				if ($lon) {
					$item->longitude = doubleval($lon);
				}
				$message = __('Geocoding successful, found {n} {results}.', array(
					"n" => count($result),
					"results" => Locale::plural(__('results'), count($result))
				));
			} else {
				$message = __('Module_Nominatim:=Unable to map your address. Is it a valid street address?');
			}
			$item->geocode_data = to_array($item->geocode_data) + array(
				'message' => $message,
				'url' => $this_url,
				'alt_url' => URL::query_append($this->url, $alt_query)
			);
			$item->store();
			return true;
		}
		$item->geocode_data = array(
			'url' => $this_url,
			'alt_url' => URL::query_append($this->url, $alt_query),
			"http_response_code" => $http->response_code,
			"http_content" => $raw
		);
		$item->geocoded = Timestamp::now();
		$item->store();
		return false;
	}
	
	/**
	 * Show credits
	 * 
	 * @todo This should probably be a configuration option
	 * @return string
	 */
	public function hook_credits() {
		return '<p class="nominatim-credit">Nominatim Search Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="/share/nominatim/images/mq_logo.png"></p>';
	}
}


/*
[
{
place_id: "1904013183",
licence: "Data OpenStreetMap contributors, ODbL 1.0. http://www.openstreetmap.org/copyright",
boundingbox: [
39.920904487876,
39.921004487876,
-75.395600627932,
-75.395500627932
],
lat: "39.9209544878757",
lon: "-75.3955506279317",
display_name: "211, West Street, Media, Delaware County, Pennsylvania, 19063, United States of America",
class: "place",
type: "house",
importance: 1.101
}
]

 */
