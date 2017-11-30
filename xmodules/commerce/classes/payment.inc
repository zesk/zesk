<?php
/**
 * @desc       $URL: https://code.marketacumen.com/zesk/trunk/modules/commerce/classes/payment.inc $
 * @package    zesk
 * @subpackage objects
 */
namespace zesk;

/**
 * Payment
 *
 * @author kent
 */
class Payment extends Object {
	/**
	 * Update expiration date on cards which have expired in the database
	 *
	 * @todo Determine expiration date of payment gateway and adjust time zone to that time when
	 *       checking expiration
	 * @return integer The number of cards which have expired
	 */
	public static function cron_cluster_minute(Application $application) {
		$query = $application->class_query_update(__CLASS__)->value('IsValid', 'false');
		$query->where('*ExpireDate|<=', $query->sql()
			->now_utc())
			->where('IsValid', true)
			->execute();
		if (($n_rows = $query->affected_rows()) > 0) {
			global $zesk;
			/* @var $zesk zesk\Kernel */
			$zesk->logger->notice("Expired {n_rows} {payments} which reached their expiration date", array(
				"n_rows" => $n_rows,
				"payments" => Locale::plural(__CLASS__, $n_rows)
			));
		}
	}
}
