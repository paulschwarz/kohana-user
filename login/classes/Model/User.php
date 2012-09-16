<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_User extends Model /*extends Model_Auth_User*/ {

	public
		$id,
		$email,
		$username,
		$password,
		$logins,
		$last_login,
		$reset_token,
		$status,
		$last_failed_login,
		$failed_login_count,
		$created,
		$modified;

	/**
	 * Set values from an array with support for one-one relationships.  This method should be used
	 * for loading in post data, etc.
	 *
	 * @param  array $values   Array of column => val
	 * @param  array $expected Array of keys to take from $values
	 * @return ORM
	 */
	public function values(array $values, array $expected)
	{
		foreach ($expected as $column)
		{
			if ( ! array_key_exists($column, $values))
				continue;

			$this->$column = $values[$column];
		}

		return $this;
	}

} // End User Model
