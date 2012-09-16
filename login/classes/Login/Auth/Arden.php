<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Arden Auth driver extended for Login module support.
 *
 * @package    Login/Auth
 * @author     Paul Schwarz
 */
class Login_Auth_Arden extends Auth {

	/**
	 * @var Repository_KohanaDatabase_User
	 */
	protected $repo_users;

	/**
	 * @var Repository_KohanaDatabase_Role
	 */
	protected $repo_roles;

	/**
	 * @var Repository_KohanaDatabase_User_Token
	 */
	protected $repo_user_tokens;

	/**
	 * @param array $config
	 */
	public function __construct($config)
	{
		parent::__construct($config);

		$this->repo_users = Repositories::fetch('users');
		$this->repo_roles = Repositories::fetch('roles');
		$this->repo_user_tokens = Repositories::fetch('user_tokens');
	}

	/**
	 * Gets the currently logged in user from the session (with auto_login check).
	 *
	 * @return  Model_User
	 */
	public function get_user($default = NULL)
	{
		$user = $this->_session->get($this->_config['session_key'], $default);

		if (is_null($user))
		{
			// check for "remembered" login
			if (($user = $this->auto_login()) === FALSE)
				return NULL;
		}

		return $user;
	}

	public function get_roles($user = NULL)
	{
		if (is_null($user))
		{
			$user = $this->get_user();
		}

		return $this->repo_roles->load_roles_by_user($user);
	}

	/**
	 * Complete the login for a user by incrementing the logins and setting
	 * session data: user_id, username, roles.
	 *
	 * @param   object  $user  user ORM object
	 * @return  void
	 */
	protected function complete_login($user)
	{
		$this->repo_users->complete_login($user);

		return parent::complete_login($user);
	}

	/**
	 * Insert an auto login token into the database and set a cookie accordingly.
	 *
	 * @param $user
	 */
	protected function generate_token($user)
	{
		$time = time();

		$token_lifetime = Kohana::$config->load('auth.lifetime');

		// Token data.
		$data = array(
			'user_id' => $user->id,
			'expires' => $time + $token_lifetime,
			'user_agent' => sha1(Request::$user_agent),
			'token' => $this->repo_user_tokens->create_token(),
			'created' => $time,
		);

		// Create a new autologin token.
		$token = (new Model_User_Token)->values($data);
		$this->repo_user_tokens->create($token);

		Cookie::set('authautologin', $token->token, $token_lifetime);
	}

	/**
	 * Forces a user to be logged in, without specifying a password.
	 *
	 * @param   mixed    $user                    username string, or user ORM object
	 * @param   boolean  $mark_session_as_forced  mark the session as forced
	 * @return  boolean
	 */
	public function force_login($user, $mark_session_as_forced = FALSE)
	{
		$user = $this->repo_users->load_user($user);

		// Mark the session as forced, to prevent users from changing account information.
		if ($mark_session_as_forced === TRUE)
			$this->_session->set('auth_forced', TRUE);

		// Run the standard completion.
		$this->complete_login($user);
	}

	/**
	 * Checks if a session is active.
	 *
	 * @param   mixed    $roles can be NULL, a role id, or an array of role ids.
	 * @return  boolean
	 */
	public function logged_in($roles = NULL)
	{
		$user = $this->get_user();

		if ( ! $user)
			return FALSE;

		if ($user instanceof Model_User)
		{
			// If we don't have a role no further checking is needed.
			if ( ! $roles)
				return TRUE;

			$roles = (array) $roles;

			$loaded_roles = $this->repo_roles->load_roles_by_name($roles);

			// Make sure all the roles are valid ones.
			if (count($roles) !== count($loaded_roles))
				return FALSE;

			return $this->repo_users->has_roles($user->id, $loaded_roles);
		}
	}

	/**
	 * Logs a user in.
	 *
	 * @param   string   $username
	 * @param   string   $password
	 * @param   boolean  $remember  enable autologin
	 * @return  boolean
	 */
	protected function _login($user, $password, $remember)
	{
		/**
		 * @var $user Model_User
		 */
		$user = $this->repo_users->load_user($user);

		if ($user === FALSE)
		{
			return FALSE;
		}

		// If there are too many recent failed logins, fail now.
		$max_failed_log_ins_allowed = Kohana::$config->load('login.auth.max_failed_logins');
		$login_jail_time = Kohana::$config->load('login.auth.login_jail_time');

		if ($max_failed_log_ins_allowed > 0)
		{
			$too_many_failed_log_ins = $user->failed_login_count >= $max_failed_log_ins_allowed;
			$still_within_jail_time = strtotime($user->last_failed_login) > strtotime('-'.$login_jail_time);

			if ($too_many_failed_log_ins && $still_within_jail_time)
			{
				$this->_session->set('jailed', TRUE);
				// Refuse login because too many failed log ins within {login_jail_time} minutes.
				return FALSE;
			}
		}

		// Attempt the actual login.
		return $this->__login($user, $password, $remember);
	}

	protected function __login($user, $password, $remember)
	{
		if (is_string($password))
		{
			// Create a hashed password.
			$password = $this->hash($password);
		}

		$login_role = $this->repo_roles->load_object(['name' => 'login']);
		$user_has_login_role = $this->repo_users->has_roles($user->id, $login_role->id);

		// If the passwords match, perform a login.
		if ($user_has_login_role && $user->password === $password)
		{
			if ($remember === TRUE)
			{
				// Generate, save and set a new unique token.
				$this->generate_token($user);
			}

			// Reset the failed login count.
			$user->failed_login_count = 0;

			// Finish the login.
			$this->complete_login($user);

			return TRUE;
		}

		// Failed login so update failed count and time.
		$this->repo_users->failed_login($user);

		// Login failed.
		return FALSE;
	}

	/**
	 * Logs a user in, based on the authautologin cookie.
	 * @return  mixed
	 */
	public function auto_login()
	{
		if ($token = Cookie::get('authautologin'))
		{
			// Load the token and user.
			$token = $this->repo_user_tokens->load_object(['token' => $token]);
			$user = $this->repo_users->load_object(['id' => $token->user_id]);

			if ($token AND $user)
			{
				// Delete old token.
				$this->repo_user_tokens->delete($token);

				// If valid, generate a new token and complete the login.
				if ($token->user_agent === sha1(Request::$user_agent))
				{
					// Generate, save and set a new unique token.
					$this->generate_token($user);

					// Complete the login with the found data.
					$this->complete_login($user);

					// Automatic login was successful.
					return $user;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Log a user out and remove any auto-login cookies.
	 *
	 * @param   boolean  $destroy     completely destroy the session
	 * @param	boolean  $logout_all  remove all tokens for user
	 * @return  boolean
	 */
	public function logout($destroy = FALSE, $logout_all = FALSE)
	{
		// Set by force_login().
		$this->_session->delete('auth_forced');

		if ($token_hash = Cookie::get('authautologin'))
		{
			// Delete the autologin cookie to prevent re-login.
			Cookie::delete('authautologin');

			// Clear the autologin token from the database.
			$token = $this->repo_user_tokens->load_object(['token' => $token_hash]);

			if ($token AND $logout_all)
			{
				// Delete all user tokens.
				$this->repo_user_tokens->delete_by_user_id($token->user_id);
			}
			elseif ($token)
			{
				$this->repo_user_tokens->delete($token);
			}
		}

		return parent::logout($destroy);
	}

	/**
	 * Get the stored password for a username.
	 *
	 * @param   mixed   $user  username or email string, or user model
	 * @return  string
	 */
	public function password($user)
	{
		$this->repo_users->load_password($user);
	}

	/**
	 * Compare password with original (hashed). Works for current (logged in) user
	 *
	 * @param   string  $password
	 * @return  boolean
	 */
	public function check_password($password)
	{
		$user = $this->get_user();

		if ( ! $user)
			return FALSE;

		return ($this->hash($password) === $user->password);
	}

	/**
	 * Register a user.
	 *
	 * @param Model_User $user
	 * @throws Kohana_Exception
	 */
	public function register(Model_User $user)
	{
		$roles = $this->repo_roles->load_roles_by_name('login');

		if (empty($roles))
		{
			throw new Kohana_Exception('Role "login" not found. Ensure that role exists in the "roles" table.');
		}

		$user->password = $this->hash($user->password);

		$this->repo_users->register_user($user, $roles);
	}

}
