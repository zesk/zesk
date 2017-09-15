<?php
namespace zesk;

/**
 *
 * @see Class_Health_Events
 *
 * @property id $id
 *
 * @property date $date
 * @property hex $hash
 *
 * @property Server $server
 * @property string $application
 * @property string $context
 * @property string $type
 * @property string $message
 * @property boolean $fatal
 *
 * @property timestamp $first
 * @property integer $first_msec
 * @property timestamp $recent
 * @property integer $recent_msec
 *
 * @property integer $total
 */
class Health_Events extends Object {
	public function store() {
		if ($this->member_is_empty('first_msec')) {
			$this->first_msec = 0;
		}
		if ($this->member_is_empty('recent_msec')) {
			$this->recent_msec = 0;
		}
		return parent::store();
	}
	public function register_from_event(Health_Event $event) {
		$hash = array(
			$event->member_integer("server"),
			$event->application,
			$event->context,
			$event->type,
			$event->message,
			intval($event->fatal)
		);
		$fields = array(
			'hash' => md5(implode("|", $hash)),
			'date' => new Date($event->when)
		);
		$this->set_member($fields);
		if ($this->find()) {
			$this->bump($event->when, $event->when_msec);
			return $this;
		}
		$this->set_member($event->members("server;application;context;type;message;fatal"));
		$this->first = $this->recent = $event->when;
		$this->first_msec = $this->recent_msec = $event->when_msec;
		$this->total = 1;
		return $this->store();
	}
	public function bump($when, $when_msec = 0) {
		$this->query_update()
			->value("*total", "total+1")
			->where("id", $this->id)
			->execute();
		$this->query_update()
			->values(array(
			"recent" => $when,
			"recent_msec" => $when_msec
		))
			->where(array(
			"id" => $this->id,
			"recent|<=" => $when
		))
			->execute();
		return $this;
	}
}
