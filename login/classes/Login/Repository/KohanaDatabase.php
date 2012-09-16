<?php

class Login_Repository_KohanaDatabase extends Arden_Repository_KohanaDatabase {

	/**
	 * Tests if this object has a relationship to a different model,
	 * or an array of different models. When providing far keys, the number
	 * of relations must equal the number of keys.
	 *
	 * // Check for the login role if you know the role's id is 5
	 * $repo->has(123, 'users_roles', 'user_id', 'role_id', 5)
	 * // Check for all of the following roles
	 * $repo->has(123, 'users_roles', 'user_id', 'role_id', [1, 2, 3, 4])
	 * // Check if $model has any roles
	 * $repo->has(123, 'users_roles', 'user_id')
	 *
	 * @param $object
	 * @param $join_table
	 * @param $near_key
	 * @param null $far_key
	 * @param null $far_keys
	 * @return bool
	 */
	public function has($object, $join_table, $near_key, $far_key = NULL, $far_keys = NULL)
	{
		$count = $this->count_relations($object, $join_table, $near_key, $far_key, $far_keys);

		if ($far_keys === NULL)
		{
			return (bool) $count;
		}
		else
		{
			return $count === count($far_keys);
		}
	}

	/**
	 * Returns the number of relations.
	 *
	 * // Counts the number of times role 5 is attached to the object
	 * $repo->count_relations(123, 'users_roles', 'user_id', 'role_id', 5)
	 * // Counts the number of times any of roles 1, 2, 3, or 4 are attached to the object
	 * $repo->count_relations(123, 'users_roles', 'user_id', 'role_id', [1, 2, 3, 4])
	 * // Counts the number roles attached to $model
	 * $repo->count_relations(123, 'users_roles', 'user_id')
	 *
	 * @param $object
	 * @param $join_table
	 * @param $near_key
	 * @param null $far_key
	 * @param null $far_keys
	 * @return int
	 */
	public function count_relations($object, $join_table, $near_key, $far_key = NULL, $far_keys = NULL)
	{
		$near_id = is_object($object) ? $object->id : $object;

		if (is_null($far_keys))
		{
			return (int) $this->new_select()
				->select(['COUNT("*")', 'records_found'])
				->from($join_table)
				->where($near_key, '=', $near_id)
				->execute($this->_database)
				->get('records_found');
		}
		else
		{
			$far_keys = (array) $far_keys;

			if ( ! $far_keys)
				return 0;

			return (int) $this->new_select()
				->select(['COUNT("*")', 'records_found'])
				->from($join_table)
				->where($near_key, '=', $near_id)
				->where($far_key, 'IN', $far_keys)
				->execute($this->_database)
				->get('records_found');
		}
	}

}
