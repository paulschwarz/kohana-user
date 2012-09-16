<?php defined('SYSPATH') OR die('No direct script access.');

class Login_Session_Database extends Kohana_Session_Database {

	// Database column names
	protected $_columns = [
		'session_id'  => 'session_id',
		'last_active' => 'last_active',
		'contents'    => 'contents',
		'user_id' 	  => 'user_id',
	];

	/**
	 * @var Model_User
	 */
	protected $user;

	public function user(Model_User $user)
	{
		$this->user = $user;
	}

	protected function _write()
	{
		if ($this->_update_id === NULL)
		{
			// Insert a new row
			$query = DB::insert($this->_table, $this->_columns)
				->values([':new_id', ':active', ':contents', ':user_id']);
		}
		else
		{
			// Update the row
			$query = DB::update($this->_table)
				->value($this->_columns['last_active'], ':active')
				->value($this->_columns['contents'], ':contents')
				->value($this->_columns['user_id'], ':user_id')
				->where($this->_columns['session_id'], '=', ':old_id');

			if ($this->_update_id !== $this->_session_id)
			{
				// Also update the session id
				$query->value($this->_columns['session_id'], ':new_id');
			}
		}

		$query
			->param(':new_id',   $this->_session_id)
			->param(':old_id',   $this->_update_id)
			->param(':active',   $this->_data['last_active'])
			->param(':contents', $this->__toString())
			->param(':user_id',  $this->user ? $this->user->id : NULL);

		// Execute the query
		$query->execute($this->_db);

		// The update and the session id are now the same
		$this->_update_id = $this->_session_id;

		// Update the cookie with the new session id
		Cookie::set($this->_name, $this->_session_id, $this->_lifetime);

		return TRUE;
	}

}
