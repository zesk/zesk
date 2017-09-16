<?php
namespace zesk;

class Feed_Post extends Model {
	
	/**
	 * 
	 * @var Feed
	 */
	public $feed = null;
	
	/**
	 * 
	 * @var Timestamp
	 */
	public $date = null;
	
	/**
	 * 
	 * @var string
	 */
	public $raw_date = null;
	
	/**
	 * 
	 * @var string
	 */
	public $link = null;
	
	/**
	 * 
	 * @var string
	 */
	public $title = null;
	
	/**
	 * 
	 * @var string
	 */
	public $description = null;
}

