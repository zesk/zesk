<?php
/**
 * 
 */

/**
 * @see Class_Content_Image
 * @author kent
 *
 */
class Content_Image extends zesk\Content_Image {
	/**
	 * 
	 */
	public static function permissions(zesk\Application $application) {
		return parent::default_permissions($application, __CLASS__);
	}
	
	/**
	 * 
	 * @param User $user
	 * @param Permission $perm
	 * @return mixed|boolean
	 */
	public function hook_permission(User $user, Permission $perm) {
		$is_mine = to_bool($this->member_query('users')
			->where('users.id', $user)
			->what("*n", "COUNT(users.id)")
			->one_integer('n'));
		return $is_mine;
	}
}
