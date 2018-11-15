<?php
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
class Help extends ORM {
    public function show() {
        $query = $this->query_update();
        $query->value('*show_first', $query->sql()
            ->now())
            ->where('show_first', null)
            ->where(array(
            'id' => $this->id(),
        ))
            ->execute();
        $query = $this->query_update();
        $query->value('*show_recent', $query->sql()
            ->now())
            ->value('*show_count', 'show_count+1')
            ->where(array(
            'id' => $this->id(),
        ))
            ->execute();
        return $this;
    }
}
