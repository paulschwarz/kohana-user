<?php defined('SYSPATH') or die('No direct script access.');

class Login_Controller_User extends Abstract_Controller_Page {

	protected $actions_require_roles = [
		'index' => 'login',
		'edit' => 'login',
		'deregister' => 'login',
		'password' => 'login',
		'complete' => 'login',
	];

	protected $reserved_usernames = [
		'admin',
		'administrator',
		'root',
		'superuser',
	];

	public function action_restricted(){}

	public function action_login()
	{
		// Allow username set by get param but otherwise prefer the $_REQUEST global array.
		list($username, $email, $password, $remember) = $this->request_params($_REQUEST, $_GET);

		// A login attempt is only valid if a username and password are supplied.
		$valid_attempt = ! is_null($username) && ! is_null($password);

		if ($this->request->is_ajax())
		{
			if ($this->login->is_logged_in() || $valid_attempt && $this->auth->login($username, $password, $remember))
				return $this->response->status(200)->body($this->ajax_response(TRUE));
			else
				return $this->response->status(500)->body($this->ajax_response(FALSE));
		}
		else
		{
			if ($this->auth->logged_in() || $valid_attempt && $this->auth->login($username, $password, $remember))
				return $this->redirect($this->session->get_once('return_url', 'user'));
			elseif ($_POST)
				$this->view('errors', $this->login_errors());
		}

		// Reset the jailed state.
		$this->session->delete('jailed');

		$this->view('username', $username);
		$this->view('allow_registration', Kohana::$config->load('login.allow_registration'));
		$this->view('enabled_providers', array_keys(array_filter(Kohana::$config->load('login.providers'))));
	}

	protected function ajax_response($success)
	{
		return __('{ "success": ":boolean" }', [':boolean' => $success ? 'true' : 'false']);
	}

	public function action_logout()
	{
		$this->auth->logout();

		// If logout worked the redirecting to the user page will redirect to the sign in page.
		$this->redirect($this->session->get_once('return_url', 'user'));
	}

	public function request_params($request_array, $get_array)
	{
		$username = Arr::get($request_array, 'username', htmlspecialchars(Arr::get($get_array, 'username', '')));
		$email = Arr::get($request_array, 'email');
		$password = Arr::get($request_array, 'password');
		$remember = Arr::get($request_array, 'remember', false) != false;

		return [$username, $email, $password, $remember];
	}

	protected function login_errors()
	{
		// Get errors for display in view.
		$validation = Validation::factory($_REQUEST)
			->rule('username', 'not_empty')
			->rule('password', 'not_empty');

		if ($validation->check())
		{
			if ($this->session->get('jailed', FALSE))
				$validation->error('username', 'jailed');
			else
				$validation->error('password', 'invalid');
		}

		return $validation->errors('user');
	}

	/**
	 * View: User account information
	 */
	public function action_index()
	{
		/**
		 * @var $repo_identities Repository_KohanaDatabase_User_Identity
		 */
		$repo_identities = Repositories::fetch('user_identities');

		$user = $this->auth->get_user();

		$active_identities = Arr::map(
			function(Model_User_Identity $identity)
			{
				return $identity->provider;
			},
			$repo_identities->load_set([['user_id' => $user->id]])
		);

		$enabled_providers = array_keys(array_filter(Kohana::$config->load('login.providers')));

		$this->_view->title = __('User profile');
		$this->_view->user = (array) $user;
		$this->_view->active_identities = $active_identities;
		$this->_view->inactive_providers = array_diff($enabled_providers, $active_identities);

		// TODO providers
	}

	/**
	 * View: Profile editor
	 */
	public function action_edit()
	{
		// Load logged in user.
		$user = $this->auth->get_user();

		if ($this->is_post() && is_numeric($user->id))
		{
			/**
			 * @var $repo_users Repository_KohanaDatabase_User
			 */
			$repo_users = Repositories::fetch('users');

			$user->values($_POST, ['username', 'email']);

			/**
			 * @var $validation Validation
			 */
			$validation = $repo_users->validation( (array) $user)
				->bind(':model', $user);

			if ($validation->check())
			{
				$repo_users->update($user);
				Message::add('success', __('Saved'));
				return $this->redirect('user');
			}
			else
			{
				Message::add('success', __('Failed'));
				$this->_view->errors = $validation->errors('User');
			}
		}

		$roles = $this->auth->get_roles($user);

		$this->_view->title = __('Edit My Profile');
		$this->_view->user = (array) $user;
		$this->_view->roles = $roles;

	}

	public function action_complete()
	{
		$user = $this->auth->get_user();

		if (Valid::email($user->email, TRUE))
		{
			$this->redirect('user');
		}

		$use_captcha = Kohana::$config->load('login.captcha');

		if ($use_captcha)
		{
			include Kohana::find_file('Vendor', 'recaptcha/recaptchalib');
			$recaptcha_config = Kohana::$config->load('recaptcha');
			$recaptcha_error = NULL;
		}

		if ($this->is_post())
		{
			$additional_checks_success = TRUE;

			if ($use_captcha)
			{
				$recaptcha_response = $this->captcha_check_answer($recaptcha_config['privatekey']);

				if (! $recaptcha_response->is_valid)
				{
					$additional_checks_success = FALSE;
					$recaptcha_error = $recaptcha_response->error;
					Message::add('error', __('The captcha text is incorrect, please try again.'));
				}
			}

			$errors = [];

			if ( ! $additional_checks_success)
			{
				$errors = Arr::merge($errors, [Kohana::message('User', 'captcha.invalid')]);
			}

			/**
			 * @var $repo_users Repository_KohanaDatabase_User
			 */
			$repo_users = Repositories::fetch('users');

			$email = $this->request->post('email');

			$validation = $repo_users->validation_email(['email' => $email])
				->bind(':model', $user);

			if ($validation->check())
			{
				$user->email = $email;
				$repo_users->update($user);
				//TODO send email
				$this->redirect($this->session->get_once('return_url', 'user'));
			}
			else
			{
				$errors = Arr::merge($validation->errors('User'), $errors);
			}

			$this->_view->errors = $errors;
			$this->_view->email = $email;
		}

		$this->_view->title = __('Complete Profile');

		if ($use_captcha)
		{
			$this->_view->captcha = recaptcha_get_html($recaptcha_config['publickey'], $recaptcha_error);
		}

	}

	/**
	 * Register a new user.
	 */
	public function action_register()
	{
		// If user already signed-in then get out of here.
		if ($this->auth->logged_in())
		{
			$this->request->redirect('user');
		}

		$use_captcha = Kohana::$config->load('login.captcha');

		if ($use_captcha)
		{
			include Kohana::find_file('Vendor', 'recaptcha/recaptchalib');
			$recaptcha_config = Kohana::$config->load('recaptcha');
			$recaptcha_error = NULL;
		}

		if ($this->is_post())
		{
			$additional_checks_success = TRUE;

			if ($use_captcha)
			{
				$recaptcha_response = $this->captcha_check_answer($recaptcha_config['privatekey']);

				if (! $recaptcha_response->is_valid)
				{
					$additional_checks_success = FALSE;
					$recaptcha_error = $recaptcha_response->error;
					Message::add('error', __('The captcha text is incorrect, please try again.'));
				}
			}

			$errors = [];

			if ( ! $additional_checks_success)
			{
				$errors = Arr::merge($errors, [Kohana::message('User', 'captcha.invalid')]);
			}

			/**
			 * @var $repo_users Repository_KohanaDatabase_User
			 */
			$repo_users = Repositories::fetch('users');

			$user = new Model_User;
			$user->values($this->request->post(), ['email', 'username', 'password', 'confirm']);
			$validation = $repo_users->validation_register( (array) $user)
				->bind(':model', $user);

			if ($validation->check())
			{
				$this->auth->register($user);
				//TODO send email
				$this->auth->force_login($user);
				$this->redirect($this->session->get_once('return_url', 'user'));
			}
			else
			{
				$errors = Arr::merge($validation->errors('User'), $errors);
			}

			$this->_view->errors = $errors;

			list($username, $email, $password, $remember) = $this->request_params($_REQUEST, $_GET);
			$this->view('username', $username);
			$this->view('email', $email);
		}

		if ($use_captcha)
		{
			$this->_view->captcha = recaptcha_get_html($recaptcha_config['publickey'], $recaptcha_error);
		}

		$this->_view->title = __('User registration');
	}

	protected function captcha_check_answer($private_key)
	{
		return recaptcha_check_answer (
			$private_key,
			$_SERVER['REMOTE_ADDR'],
			$_POST['recaptcha_challenge_field'],
			$_POST['recaptcha_response_field']
		);
	}

	/**
	 * Close the current user's account.
	 */
	public function action_deregister()
	{
		$this->_view->title = __('Close my account');

		$user = $this->auth->get_user();

		// Double check that the id really does belong to the logged in user.
		$this->_view->id = $id_hash = $this->auth->hash($user->id);

		if ($this->request->post('confirmation') === $id_hash)
		{
			$this->auth->logout();

			/**
			 * @var $repo_users Repository_KohanaDatabase_User
			 * @var $repo_user_identities Repository_KohanaDatabase_User_Identity
			 */
			$repo_users = Repositories::fetch('users');
			$repo_user_identities = Repositories::fetch('user_identities');

			$repo_users->delete($user);
			$repo_user_identities->delete_by_user_id($user->id);

			Message::add('success', __('Successfully deregistered user account.'));
			$this->redirect($this->session->get_once('return_url', 'user'));
		}
	}

	/**
	 * A basic implementation of the "Forgot password" functionality
	 */
	public function action_forgot()
	{
		// Password reset must be enabled in config/useradmin.php
		if ( ! Kohana::$config->load('login.email'))
		{
			Message::add('error', 'Password reset via email is not enabled.
			Please contact the site administrator to reset your password.');

			return $this->redirect('user/login');
		}

		$this->_view->title = __('Forgot password');

		if ($this->is_post())
		{
			/**
			 * @var $repo_users Repository_KohanaDatabase_User
			 * @var $user Model_User
			 */
			$repo_users = Repositories::fetch('users');
			$user = $repo_users->load_object(['email' => $this->request->post('email')]);

			if ($user)
			{
				if (in_array($user->username, $this->reserved_usernames))
				{
					Message::add('error', __('Admin account password cannot be reset via email.'));
				}
				else
				{
					$user->reset_token = Text::random('distinct', 32);
					$repo_users->update($user);
					//TODO send email
					Message::add('success', __('Password reset email sent.'));
					$this->redirect('user/login');
				}
			}
			else
			{
				Message::add('error', __('User account for that email could not be found.'));
			}
		}
	}

	/**
	 * A basic version of "reset password" functionality.
	 */
	function action_reset()
	{
		// Password reset must be enabled in config/useradmin.php
		if ( ! Kohana::$config->load('login.email'))
		{
			Message::add('error', 'Password reset via email is not enabled.
			Please contact the site administrator to reset your password.');

			return $this->redirect('user/login');
		}

		$this->_view->title = __('Reset password');

		if (isset($_REQUEST['token']) && isset($_REQUEST['email']))
		{
			$token = trim($_REQUEST['token']);
			$email = trim($_REQUEST['email']);

			if (Valid::exact_length($token, 32) && Valid::email($email))
			{
				/**
				 * @var $repo_users Repository_KohanaDatabase_User
				 * @var $user Model_User
				 */
				$repo_users = Repositories::fetch('users');

				$user = $repo_users->load_object([
					'email' => $this->request->post('email'),
					'reset_token' => $token,
				]);

				if ($user)
				{
					if (in_array($user->username, $this->reserved_usernames))
					{
						Message::add('error', __('Admin account password cannot be reset via email.'));
					}
					elseif ($user->reset_token === $token)
					{
						$user->reset_token = NULL;
						$repo_users->update($user);

						$this->auth->force_login($user, FALSE);
						return $this->redirect('user/password');
					}
				}

			}
		}

	}

	/**
	 * Allow the user to change their password.
	 */
	function action_password()
	{
		// Load logged in user.
		$user = $this->auth->get_user();

		if ($this->is_post() && is_numeric($user->id))
		{
			/**
			 * @var $repo_users Repository_KohanaDatabase_User
			 */
			$repo_users = Repositories::fetch('users');

			$user->values($this->request->post(), ['password', 'confirm']);
			$validation = $repo_users->validation_password( (array) $user);

			if ($validation->check())
			{
				$user->password = $this->auth->hash($this->request->post('password'));

				$repo_users->update($user);

				Message::add('success', __('Saved'));
				return $this->redirect('user');
			}
			else
			{
				Message::add('success', __('Failed'));
				$this->_view->errors = $validation->errors('User');
			}
		}
	}

	/**
	 * Redirect to the provider's auth URL.
	 */
	function action_provider ()
	{
		$provider_name = $this->request->param('provider');

		if ($this->auth->logged_in())
		{
			Message::add('success', 'Already logged in.');
			return $this->redirect('user');
		}

		$provider = Provider::factory($provider_name);

		if ($this->request->query('code') && $this->request->query('state'))
		{
			return $this->provider_return($provider_name);
		}

		if (is_object($provider))
		{
			return $this->redirect($provider->redirect_url('/user/provider_return/'.$provider_name));
		}

		Message::add('error', 'Provider is not enabled. Please select another provider or log in normally.');
		return $this->redirect('user/login');
	}

	function action_provider_return()
	{
		$provider_name = $this->request->param('provider');

		$this->provider_return($provider_name);
	}

	function action_associate()
	{
		$provider_name = $this->request->param('provider');

		if ($this->request->query('code') && $this->request->query('state'))
		{
			return $this->associate_return($provider_name);
		}

		if ($this->auth->logged_in())
		{
			if ($this->request->post() && $this->request->post('provider') === $provider_name)
			{
				$provider = Provider::factory($provider_name);

				if (is_object($provider))
				{
					return $this->redirect($provider->redirect_url('/user/associate_return/' . $provider_name));
				}
				else
				{
					Message::add('error', 'Provider is not enabled; please select another provider or log in normally.');
					return $this->redirect('user/login');
				}
			}
		}
		else
		{
			Message::add('error', 'You are not logged in.');
			return $this->redirect('user/login');
		}

		$this->_view->title = __('Confirm associating your user account');
		$this->_view->provider_name = $provider_name;
	}

	function action_associate_return()
	{
		$provider_name = $this->request->param('provider');

		$this->associate_return($provider_name);
	}

	/**
	 * Associate a logged in user with an account.
	 *
	 * Note that you should not trust the OAuth/OpenID provider-supplied email
	 * addresses. Yes, for Facebook, Twitter, Google and Yahoo the user is actually
	 * required to ensure that the email is in fact one that they control.
	 *
	 * However, with generic OpenID (and non-trusted OAuth providers) one can setup a
	 * rogue provider that claims the user owns a particular email address without
	 * actually owning it. So if you trust the email information, then you open yourself to
	 * a vulnerability since someone might setup a provider that claims to own your
	 * admin account email address and if you don't require the user to log in to
	 * associate their account they gain access to any account.
	 *
	 * TL;DR - the only information you can trust is that the identity string is
	 * associated with that user on that openID provider, you need the user to also
	 * prove that they want to trust that identity provider on your application.
	 *
	 */
	function associate_return($provider_name = null)
	{
		if ($this->auth->logged_in())
		{
			$provider = Provider::factory($provider_name);

			// Verify the request.
			if (is_object($provider) && $provider->verify())
			{
				$user = $this->auth->get_user();

				if ( ! is_null($user))
				{
					/**
					 * @var $repo_user_identities Repository_KohanaDatabase_User_Identity
					 */
					$repo_user_identities = Repositories::fetch('user_identities');

					$matching_identity = $repo_user_identities->load_object([
						'user_id' => $user->id,
						'provider' => $provider_name,
					]);

					if ($matching_identity)
					{
						Message::add('warning', __('Your user account is already associated with :provider.', [
							':provider'	=> ucfirst($provider_name),
						]));

						return $this->redirect('user');
					}

					$user_identity = new Model_User_Identity;
					$user_identity->user_id = $user->id;
					$user_identity->provider = $provider_name;
					$user_identity->identity = $provider->user_id();

					$validation = $repo_user_identities->validation( (array) $user_identity);

					if ($validation->check())
					{
						$repo_user_identities->create($user_identity);

						Message::add('success', __('Your user account has been associated with :provider.', [
							':provider'	=> ucfirst($provider_name),
						]));

						return $this->redirect('user');
					}
					else
					{
						Message::add('error', 'We were unable to associate this account with the provider.
						Please make sure that there are no other accounts using this provider identity,
						as each 3rd party provider identity can only be associated with one user account.');

						return $this->request->redirect('user/login');
					}
				}
			}
		}

		Message::add('error', 'There was an error associating your account with this provider.');
		return $this->request->redirect('user/login');
	}

	/**
	 * Allow the user to login and register using a 3rd party provider.
	 * @param string $provider_name
	 */
	function provider_return($provider_name = NULL)
	{
		$provider = Provider::factory($provider_name);

		$return_url = $this->session->get_once('return_url', 'user');

		if ( ! is_object($provider))
		{
			Message::add('error', 'Provider is not enabled. Please select another provider or log in normally.');
			return $this->redirect('user/login');
		}

		// verify the request
		if ($provider->verify())
		{
			/**
			 * @var $repo_user_identities Repository_KohanaDatabase_User_Identity
			 * @var $repo_users Repository_KohanaDatabase_User
			 * @var $repo_roles Repository_KohanaDatabase_Role
			 */
			$repo_user_identities = Repositories::fetch('user_identities');
			$repo_users = Repositories::fetch('users');
			$repo_roles = Repositories::fetch('roles');

			/**
			 * @var $user_identity Model_User_Identity
			 */
			$user_identity = $repo_user_identities->load_object([
				'provider' => $provider_name,
				'identity' => $provider->user_id(),
			]);

			if (is_object($user_identity))
			{
				$user = $repo_users->load_object(['id' => $user_identity->user_id]);

				if ($user->id === $user_identity->user_id && is_numeric($user->id))
				{
					$this->auth->force_login($user);
					return $this->redirect($return_url);
				}
			}

			// Register account.
			if ( ! $this->auth->logged_in())
			{
				$values = [
					'username' => $repo_users->generate_username(
						str_replace(' ', '.', $provider->name())
					),
					'password' => $this->auth->hash(Text::random('distinct', 42)),
				];

				// If the provider supplies the email then use it, otherwise set it to '' and require user input later.
				if (Valid::email($provider->email(), TRUE))
				{
					$values['email'] = $provider->email();
				}
				else
				{
					$values['email'] = '';
				}

				$user = (new Model_User)->values($values, ['username', 'password', 'email']);

				/**
				 * @var $validation Validation
				 */
				$validation = $repo_users->validation_provider( (array) $user)
					->bind(':model', $user);

				if ($validation->check())
				{
					$roles = $repo_roles->load_roles_by_name('login');

					if (empty($roles))
					{
						throw new Kohana_Exception('Role "login" not found. Ensure that role exists in the "roles" table.');
					}

					$repo_users->register_user_with_identity($user, $roles, $provider_name, $provider->user_id());

					$this->auth->force_login($user);
					return $this->redirect($return_url);
				}
				else
				{
					Message::add('error', __('Failed'));

					$errors = $validation->errors('User');

					Log::instance()->add(Log::WARNING, 'Problem logging in using provider ":provider". :errors', [
						':provider' => $provider_name,
						':errors' => print_r($errors, TRUE),
					]);

					return $this->redirect($return_url);
				}
			}
			else
			{
				Message::add('error', 'You are logged in, but the email received from the provider does not match
				the email associated with your account.');

				return $this->redirect('user');
			}
		}
		else
		{
			Message::add('error', 'Retrieving information from the provider failed. Please register below.');

			return $this->request->redirect('user/register');
		}
	}


}
