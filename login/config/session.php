<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'database' => array(
		'columns' => array(
			/**
			 * session_id:  session identifier
			 * last_active: timestamp of the last activity
			 * contents:    serialized session data
			 */
			'session_id'  => 'session_id',
			'user_id'  	  => 'user_id',
			'last_active' => 'last_active',
			'contents'    => 'contents'
		),
	),
);
