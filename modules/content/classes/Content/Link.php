<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/Content/Link.php $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Content_Link
 *
 * @see Class_Content_Link
 */
class Content_Link extends ORM {
	function store() {
		$this->Hash = md5($this->URL);
		if ($this->Parent === 0 || $this->Parent === "0" || $this->Parent === "") {
			$this->Parent = null;
		}
		return parent::store();
	}
	function image($image_size = 150) {
		$options['image_path'] = "/data/link";
		$options['image_size'] = $image_size;
		$options['image_field'] = "ImagePath";
		$options['is_relative'] = false;
		$options['root_directory'] = $this->application->document_root();
		
		return $this->output('image/image-caption.tpl', $options);
	}
	function clicked() {
		$this->query_update()
			->values(array(
			"*ClickCount" => "ClickCount+1",
			"*LastClick" => $this->sql()
				->now()
		))
			->where("ID", $this->id())
			->execute();
		$this->query_update()
			->values(array(
			"*FirstClick" => $this->sql()
				->now()
		))
			->where("ID", $this->id())
			->where("FirstClick", null)
			->execute();
	}
}

