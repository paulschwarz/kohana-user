<?php

class Repository_KohanaDatabase_User extends Repository_KohanaDatabase {

	protected $_roles_users = 'roles_users';
	protected $_user_identities = 'user_identities';

	public $rules_password, $rules_email;

	protected function initialize()
	{
		$this->rules_username = [
			'username' => [
				['not_empty'],
				['max_length', [':value', 32]],
				[[$this, 'unique'], ['username', ':value', ':model']],
			],
		];

		$this->rules_email = [
			'email' => [
				['not_empty'],
				['email'],
				[[$this, 'unique'], ['email', ':value', ':model']],
			],
		];

		$this->rules_password = [
			'password' => [
				['not_empty'],
				['min_length', [':value', 8]],
			],
			'confirm' => [
				['matches', [':validation', 'password', ':field']],
			],
		];
	}

	public function load_user($user)
	{
		if ( ! is_object($user))
		{
			// Load the User model.
			$username = $user;
			$key = $this->unique_key($username);
			$user = $this->load_object([$key => $username]);
		}

		return $user;
	}

	/**
	 * @param string|Model_User
	 * @return string
	 */
	public function load_password($user)
	{
		if (is_string($user))
		{
			$key = $this->unique_key($user);
			$user = $this->load_object([$key, $user]);
		}

		return $user->password;
	}

	/**
	 * Allows a model use both email and username as unique identifiers for login
	 *
	 * @param   string  unique value
	 * @return  string  field name
	 */
	public function unique_key($value)
	{
		return Valid::email($value) ? 'email' : 'username';
	}

	public function failed_login($user)
	{
		$user->failed_login_count = $user->failed_login_count + 1;
		$user->last_failed_login = date("Y-m-d H:i:s");

		// Verify if the user id if valid before save it.
		if(is_numeric( $user->id ) && $user->id != 0)
		{
			$this->update($user);
		}
	}

	/**
	 * Complete the login for a user by incrementing the log ins and saving login timestamp.
	 *
	 * @return void
	 */
	public function complete_login($user)
	{
		// Update the number of logins.
		$user->logins = $user->logins + 1;

		// Set the last login date.
		$user->last_login = time();

		$this->update($user);
	}

	public function has_roles($user_id, $roles = NULL)
	{
		$role_ids = $this->pluck_role_ids($roles);

		return $this->has((int) $user_id, $this->_roles_users, 'user_id', 'role_id', $role_ids);
	}

	protected function pluck_role_ids($roles)
	{
		if ( ! is_null($roles))
		{
			$roles = (array) $roles;

			$role_ids = [];

			foreach ($roles as $role)
			{
				$role_ids[] = is_object($role) ? $role->id : (int) $role;
			}

			return $role_ids;
		}

		return NULL;
	}

	/**
	 * Checks whether a column value is unique.
	 * Excludes itself if loaded.
	 *
	 * @param   string   $field  the field to check for uniqueness
	 * @param   mixed    $value  the value to check for uniqueness
	 * @param   Model_User $model ignore the model itself
	 * @return  bool     whether the value is unique
	 */
	public function unique($field, $value, $model)
	{
		$query = $this->new_select()
			->select([DB::expr('COUNT(`username`)'), 'total'])
			->from($this->_table_name)
			->where($field, '=', $value);

		if ( ! is_null($model))
		{
			$query->where('id', '!=', $model->id);
		}

		$total = (int) $query->execute($this->_database)
			->get('total');

		return $total === 0;
	}

	public function register_user(Model_User $user, array $roles)
	{
		return Transaction::factory($this->_database,
			function() use ($user, $roles)
			{
				$user = $this->create_user($user);

				$this->add_roles($user, $roles);

				return $user;
			}
		)->execute();
	}

	public function register_user_with_identity(Model_User $user, array $roles, $provider_name, $provider_id)
	{
		return Transaction::factory($this->_database,
			function() use ($user, $roles, $provider_name, $provider_id)
			{
				$user = $this->create_user($user);

				$this->add_roles($user, $roles);

				$identity = (new Model_User_Identity)->values([
					'user_id'	=> $user->id,
					'provider'	=> $provider_name,
					'identity'	=> $provider_id,
				]);

				$this->add_identity($identity);

				return $user;
			}
		)->execute();
	}

	/**
	 * @param Model_User $user
	 */
	public function create_user(Model_User $user)
	{
		$user->created = date("Y-m-d H:i:s");
		$user->modified = date("Y-m-d H:i:s");
		$user->logins = 0;
		$user->reset_token = '';
		$user->status = '';
		$user->last_failed_login = '0000-00-00 00:00:00';
		$user->failed_login_count = 0;

		return $this->create($user);
	}

	/**
	 * @param Model_User $user
	 * @param array[Model_Role] $roles
	 */
	public function add_roles(Model_User $user, array $roles)
	{
		$insert = $this->new_insert()
			->table($this->_roles_users)
			->columns(['user_id', 'role_id']);

		foreach($roles as $role)
		{
			$insert->values([$user->id, $role->id]);
		}

		$insert->execute($this->_database);
	}

	public function add_identity(Model_User_Identity $identity)
	{
		$this->new_insert()
			->table($this->_user_identities)
			->columns(['user_id', 'provider', 'identity'])
			->values([$identity->user_id, $identity->provider, $identity->identity])
			->execute($this->_database);
	}

	public function validation($data)
	{
		$validation = Validation::factory($data);

		foreach($this->rules_username as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		foreach($this->rules_email as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		return $validation;
	}

	public function validation_email($data)
	{
		$validation = Validation::factory($data);

		foreach($this->rules_email as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		return $validation;
	}

	/**
	 * @param $data array to be validated.
	 * @return Validation
	 */
	public function validation_password($data)
	{
		$validation = Validation::factory($data);

		foreach($this->rules_password as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		return $validation;
	}

	/**
	 * @param $data array to be validated.
	 * @return Validation
	 */
	public function validation_register($data)
	{
		$validation = Validation::factory($data);

		foreach($this->rules_username as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		foreach($this->rules_email as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		foreach($this->rules_password as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		return $validation;
	}

	/**
	 * @param $data array to be validated.
	 * @return Validation
	 */
	public function validation_provider($data)
	{
		$validation = Validation::factory($data);

		foreach($this->rules_username as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		return $validation;
	}

	/**
	 * Given a string, this function will try to find an unused username by appending a number.
	 * Ex. username2, username3, username4 ...
	 *
	 * @param string $base
	 * @return string $username
	 */
	function generate_username($base = '')
	{
		$base = $this->transcribe($base);
		$username = $base;

		$i = 2;

		// Check for existent username.
		while( ! $this->unique('username', $username, NULL) )
		{
			$username = $base.$i;
			$i++;
		}

		return $username;
	}

	/**
	 * Transcribe name to ASCII
	 *
	 * @param string $string
	 * @return string
	 */
	function transcribe($string)
	{
		$string = strtr($string,
			"\xA1\xAA\xBA\xBF\xC0\xC1\xC2\xC3\xC5\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD8\xD9\xDA\xDB\xDD\xE0\xE1\xE2\xE3\xE5\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF8\xF9\xFA\xFB\xFD\xFF\xC4\xD6\xE4\xF6",
			"_ao_AAAAACEEEEIIIIDNOOOOOUUUYaaaaaceeeeiiiidnooooouuuyyAOao"
		);
		$string = strtr($string, array("\xC6"=>"AE", "\xDC"=>"Ue", "\xDE"=>"TH", "\xDF"=>"ss",	"\xE6"=>"ae", "\xFC"=>"ue", "\xFE"=>"th"));
		$string = preg_replace("/([^a-z0-9\\.]+)/", "", strtolower($string));
		return($string);
	}

}
