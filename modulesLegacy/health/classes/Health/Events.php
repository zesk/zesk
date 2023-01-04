<?php declare(strict_types=1);
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
class Health_Events extends ORMBase {
	public function store(): self {
		if ($this->memberIsEmpty('first_msec')) {
			$this->first_msec = 0;
		}
		if ($this->memberIsEmpty('recent_msec')) {
			$this->recent_msec = 0;
		}
		return parent::store();
	}

	public function register_from_event(Health_Event $event) {
		$hash = [
			$event->memberInteger('server'),
			$event->application,
			$event->context,
			$event->type,
			$event->message,
			intval($event->fatal),
		];
		$fields = [
			'hash' => md5(implode('|', $hash)),
			'date' => new Date($event->when),
		];
		$this->setMember($fields);
		if ($this->find()) {
			$this->bump($event->when, $event->when_msec);
			return $this;
		}
		$this->setMember($event->members('server;application;context;type;message;fatal'));
		$this->first = $this->recent = $event->when;
		$this->first_msec = $this->recent_msec = $event->when_msec;
		$this->total = 1;
		return $this->store();
	}

	public function bump($when, $when_msec = 0) {
		$this->queryUpdate()
			->value('*total', 'total+1')
			->addWhere('id', $this->id)
			->execute();
		$this->queryUpdate()
			->values([
				'recent' => $when,
				'recent_msec' => $when_msec,
			])
			->where([
				'id' => $this->id,
				'recent|<=' => $when,
			])
			->execute();
		return $this;
	}
}
