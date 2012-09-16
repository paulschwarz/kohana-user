<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Logic relating to any web page can go here.
 * Usually, this includes loading the logged in user, and sending the object
 * to the `_view` object.
 */
abstract class Login_Abstract_Controller_Page extends Abstract_Controller_Base {

	/**
	 * @var Login_Auth_Arden
	 */
	protected $auth;

	/**
	 * Controls access for the whole controller, if not set to FALSE we will only allow user roles specified.
	 *
	 * Can be set to a string or an array, for example array('login', 'admin') or 'login'
	 */
	protected $controller_requires_roles = FALSE;

	/**
	 * Controls access for separate actions.
	 *
	 * Examples:
	 * 'adminpanel' => 'admin' will only allow users with the role admin to access action_adminpanel
	 * 'moderatorpanel' => array('login', 'moderator') will only allow users with the roles login and moderator to access action_moderatorpanel
	 */
	protected $actions_require_roles = FALSE;

	/**
	 * Called from before() when the user does not have the correct rights to access a controller/action.
	 *
	 * Override this in your own Controller / Controller_App if you need to handle
	 * responses differently.
	 *
	 * For example:
	 * - handle JSON requests by returning a HTTP error code and a JSON object
	 * - redirect to a different failure page from one part of the application
	 */
	public function access_denied()
	{
		$this->redirect('user/restricted');
	}

	/**
	 * Called from before() when the user is not logged in but they should.
	 *
	 * Override this in your own Controller / Controller_App.
	 */
	public function login_required()
	{
		$this->session->set('return_url', $this->request->uri());
		$this->redirect('user/login');
	}

	protected $_complete_profile = 'user/complete';

	public function complete_profile()
	{
		$this->session->set('return_url', $this->request->uri());
		$this->redirect($this->_complete_profile);
	}

	public function __construct(Request $request, Response $response, Session $session, $redirection, Auth $auth)
	{
		parent::__construct($request, $response, $session, $redirection);
		$this->auth = $auth;
	}

	public function is_post()
	{
		return $this->request->method() === HTTP_Request::POST;
	}

	/**
	 * The before() method is called before your controller action.
	 * In our template controller we override this method so that we can
	 * set up default values. These variables are then available to our
	 * controllers if they need to be modified.
	 *
	 * @return  void
	 */
	public function before()
	{
		// Execute parent::before first.
		parent::before();

		// If we're not logged in, but auth type supports auto login then take this chance to attempt login.
		$supports_auto_login = (new ReflectionClass(get_class($this->auth)))->hasMethod('auto_login');

		if( ! $this->auth->logged_in() && $supports_auto_login)
		{
			$this->auth->auto_login();
		}

		$user = $this->auth->get_user();

		// If the account was registered via a provider some profile information might not yet be complete.
		if ($user && empty($user->email) && $this->request->uri() != $this->_complete_profile)
		{
			$this->complete_profile();
		}

		$secured_controller_denied = $secured_action_denied = FALSE;

		// Deny if Auth is required AND user role given in auth_required is NOT logged in.
		$controller_requires_role = $this->controller_requires_roles !== FALSE;
		if ($controller_requires_role)
		{
			$user_has_secured_controller_role = $this->auth->has_role($this->controller_requires_roles);
			$secured_controller_denied = ! $user_has_secured_controller_role;
		}

		// Deny if secure_actions is set AND the user role given in secure_actions is NOT logged in.
		$has_secure_actions = is_array($this->actions_require_roles);
		if ($has_secure_actions)
		{
			$action_name = $this->request->action();
			$is_secured_action = array_key_exists($action_name, $this->actions_require_roles);
			if ($is_secured_action)
			{
				$user_has_secured_action_role = $this->auth->logged_in($this->actions_require_roles[$action_name]);
				$secured_action_denied = ! $user_has_secured_action_role;
			}
		}

		if ($secured_controller_denied || $secured_action_denied)
		{
			if ($this->auth->logged_in())
			{
				$this->access_denied();
			}
			else
			{
				$this->login_required();
			}
		}

		if ($this->is_post())
		{
			$csrf = $this->request->post('csrf-token');

			if (Request::post_max_size_exceeded())
			{
				die('max post size exceeded');
			}
			elseif(empty($csrf) || ! CSRF::valid($csrf))
			{
				die('invalid csrf security token');
			}
		}
	}

}
