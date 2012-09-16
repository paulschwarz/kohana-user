<?php

class Repository_KohanaDatabase_User_Token extends Repository_KohanaDatabase {

	public function create_token()
	{
		do
		{
			$token = sha1(uniqid(Text::random('alnum', 32), TRUE));
		}
		while ($this->token_exists($token));

		return $token;
	}

	public function token_exists($token)
	{
		return (bool) $this->load_object(['token' => $token]);
	}

	public function delete_by_user_id($user_id)
	{
		$delete = $this->new_delete();
		return (bool) $delete->table($this->_table_name)->where('user_id', '=', $user_id)->execute($this->_database);
	}

}
