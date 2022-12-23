<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @author kent
 * @see Class_Help
 * @property id $id
 * @property string $target
 * @property string $type
 * @property array $map
 * @property string $title
 * @property string $content
 * @property array $content_wraps
 * @property integer $require_user
 * @property boolean $active
 * @property Timestamp $created
 * @property Timestamp $modified
 * @property timestamp $show_first
 * @property timestamp $show_recent
 * @property integer $show_count
 */
class Help extends ORMBase {
	public function show() {
		$query = $this->queryUpdate();
		$query->value('*show_first', $query->sql()
			->now())
			->addWhere('show_first', null)
			->where([
				'id' => $this->id(),
			])
			->execute();
		$query = $this->queryUpdate();
		$query->value('*show_recent', $query->sql()
			->now())
			->value('*show_count', 'show_count+1')
			->where([
				'id' => $this->id(),
			])
			->execute();
		return $this;
	}
}
