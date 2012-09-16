<?php

class Repository_KohanaDatabase_Role extends Repository_KohanaDatabase {

	/**
	 * @param $roles string|array Roles to load e.g. 'login', ['login', 'admin'].
	 * @return array
	 */
	public function load_roles_by_name($roles)
	{
		$roles = (array) $roles;

		$select = $this->new_select()
			->as_object($this->_model_class)
			->from($this->_table_name)
			->where('name', 'IN', $roles);

		return $this->as_array($select->execute($this->_database));
	}

	public function load_roles_by_user($user)
	{
		$user_id = is_object($user) ? $user->id : $user;

		$select = $this->new_select()
			->as_object($this->_model_class)
			->select('id', 'name', 'description')
			->from('roles_users')
			->join('roles')
			->on('roles_users.role_id', '=', 'roles.id')
			->where('user_id', '=', $user_id);

		return $this->as_array($select->execute($this->_database));
	}

}
