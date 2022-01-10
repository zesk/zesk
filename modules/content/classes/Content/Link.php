<?php declare(strict_types=1);
/**
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
	public function store(): self {
		$this->Hash = md5($this->URL);
		if ($this->Parent === 0 || $this->Parent === "0" || $this->Parent === "") {
			$this->Parent = null;
		}
		return parent::store();
	}

	public function image($image_size = 150) {
		$options['image_path'] = "/data/link";
		$options['image_size'] = $image_size;
		$options['image_field'] = "ImagePath";
		$options['is_relative'] = false;
		$options['root_directory'] = $this->application->document_root();

		return $this->theme('image/image-caption', $options);
	}

	public function clicked(): void {
		$this->query_update()
			->values([
			"*ClickCount" => "ClickCount+1",
			"*LastClick" => $this->sql()
				->now(),
		])
			->where("ID", $this->id())
			->execute();
		$this->query_update()
			->values([
			"*FirstClick" => $this->sql()
				->now(),
		])
			->where("ID", $this->id())
			->where("FirstClick", null)
			->execute();
	}
}
