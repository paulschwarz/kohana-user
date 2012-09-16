<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Oauth 2.0 using Facebook's own API class.
 * If Oauth 2.0 becomes more common, a base class could be created to abstract away from Facebook.
 */
class Login_Provider_Facebook extends Provider {

	/**
	 * @var Facebook|null
	 */
	private $facebook = NULL;

	private $me = NULL;

	private $uid = NULL;

	public function __construct()
	{
		include_once Kohana::find_file('vendor', 'facebook/src/facebook');

		$facebook_config = Kohana::$config->load('facebook');
		
		// Create our Facebook SDK instance.
		$this->facebook = new Login_Provider_Implementation_Facebook(array(
			'appId'  => $facebook_config->app_id,
			'secret' => $facebook_config->secret,
			'cookie' => TRUE // enable optional cookie support
		), Session::instance());
	}

	/**
	 * Get the URL to redirect to.
	 * @param $return_url
	 * @return string
	 */
	public function redirect_url($return_url)
	{
		return $this->facebook->getLoginUrl(array(
			'next'       => URL::site($return_url, TRUE),
			'cancel_url' => URL::site($return_url, TRUE),
			'req_perms'  => 'email'
		));
	}

	/**
	 * Verify the login result and do whatever is needed to access the user data from this provider.
	 * @return bool
	 */
	public function verify()
	{
		if ($this->facebook->getUser())
		{
			try
			{
				$this->uid = $this->facebook->getUser();

				// read user info as array from Graph API
				$this->me = $this->facebook->api('/me');
			}
			catch (FacebookApiException $e)
			{
				return FALSE;
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Attempt to get the provider user ID.
	 * @return mixed
	 */
	public function user_id()
	{
		return $this->uid;
	}

	/**
	 * Attempt to get the email from the provider (e.g. for finding an existing account to associate with).
	 * @return string
	 */
	public function email()
	{
		if (isset($this->me['email']))
		{
			return $this->me['email'];
		}
		return '';
	}

	/**
	 * Get the full name (firstname surname) from the provider.
	 * @return string
	 */
	public function name()
	{
		if (isset($this->me['first_name']))
		{
			return $this->me['first_name'] . ' ' . $this->me['last_name'];
		}
		return '';
	}
}
