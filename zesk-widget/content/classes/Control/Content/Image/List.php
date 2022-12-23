<?php
declare(strict_types=1);
/**
 * @todo Move this to correct location
 */

namespace zesk;

/**
 * @author kent
 *
 */
class Control_List_Content_Image extends Control_List {
	public function hook_widgets() {
		$spec = [];

		$spec[] = $f = $this->widgetFactory("zesk\View_Link")->names('Name', 'Title')->format('{name}');

		// TODO i18n
		$spec[] = $f = $this->widgetFactory("zesk\View_Date")->names('Released', 'Released')->format('{mm}/{dd}/{yyyy}');

		$spec[] = $f = $this->widgetFactory("zesk\View_Text")->names('Summary', 'Summary')->setShowSize(200);

		$spec[] = $f = $this->widgetFactory("zesk\View_Actions");

		return $spec;
	}
}
