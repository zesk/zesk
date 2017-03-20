<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Section/End.php $
 * @package zesk
 * @subpackage widgets
 * @author kent <kent@marketacumen.com>
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Tue,Sep 22, 09 at 1:16 PM
 */
namespace zesk;

class View_Section_End extends View
{
	function __construct($options=false)
	{
		parent::__construct($options);
		$this->set_option(array('is_section_end' => true, 'is_section' => true));
	}
}
