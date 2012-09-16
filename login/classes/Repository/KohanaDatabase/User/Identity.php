<?php

class Repository_KohanaDatabase_User_Identity extends Repository_KohanaDatabase {

	protected function initialize()
	{
		$this->rules = [
			'user_id' => [
				['not_empty'],
				['numeric'],
			],
			'provider' => [
				['not_empty'],
			],
			'identity' => [
				['not_empty'],
			],
		];
	}

	public function delete_by_user_id($user_id)
	{
		$delete = $this->new_delete();
		return (bool) $delete->table($this->_table_name)->where('user_id', '=', $user_id)->execute($this->_database);
	}

}
