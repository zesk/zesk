<?php
/**
 * @see Class_Content_Image
 * @author kent
 *
 */
class Content_Image extends Zesk_Content_Image {

	public static function permissions() {
		return parent::default_permissions(__CLASS__);
	}

	public function hook_permission(User $user, Permission $perm) {
		$is_mine = to_bool($this->member_query('users')->where('users.id', $user)->what("*n", "COUNT(users.id)")->one_integer('n'));
		return $is_mine;
	}

}
