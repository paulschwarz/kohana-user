<?php defined('SYSPATH') or die('No direct script access.');

abstract class Login_Abstract_Controller_Base extends Controller {

	/**
	 * This view is rendered into the Response body in `after()`
	 * @var object the content View object
	 */
	protected $_view;

	protected $session, $redirection;

	public function __construct(Request $request, Response $response, Session $session, Redirection $redirection)
	{
		parent::__construct($request, $response);
		$this->session = $session;
		$this->redirection = $redirection;
	}

	/**
	 * Issues a HTTP redirect.
	 *
	 * @param  string  $uri   URI to redirect to
	 * @param  int     $code  HTTP Status code to use for the redirect
	 * @throws HTTP_Exception
	 */
	public function redirect($uri = '', $code = 302)
	{
		$this->redirection->redirect($uri, $code);
	}

	protected function view($key, $val = NULL)
	{
		if ( ! is_null($this->_view))
		{
			if (is_null($val))
			{
				return $this->_view->{$key};
			}
			else
			{
				$this->_view->{$key} = $val;
			}
		}
	}

}
