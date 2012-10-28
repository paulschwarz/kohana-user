<?php

return
[
	'username_or_email' =>
	[
		'label'			=> 'Username or email',
		'not_empty' 	=> 'Username must not be empty.',
	],
	'username' =>
	[
		'label'			=> 'Username',
		'not_empty' 	=> 'Username must not be empty.',
		'unique' 		=> 'Username already in use. Please select a different one.',
        'jailed'		=> 'Too many failed log in attempts. Please try again after 5 minutes.',
	],
	'email' =>
	[
		'label'			=> 'Email',
		'email'			=> 'Email must be a valid address.',
		'invalid'		=> 'Email must be a valid address.',
		'not_empty' 	=> 'Email must not be empty.',
		'unique' 		=> 'Email already in use. Please select a different one.',
	],
	'password' =>
	[
		'label'			=> 'Password',
		'not_empty'     => 'Password must not be empty.',
		'invalid' 		=> 'Username or password are incorrect.',
	],
	'confirm' =>
	[
		'label'			=> 'Confirm Password',
		'not_empty'     => 'Password must not be empty.',
		'min_length'	=> 'Password must be at least 6 characters.',
		'match'			=> 'Confirmation does not match password.',
	],
	'token' =>
	[
		'label'			=> 'Reset Token',
	],
	'remember' =>
	[
		'label'			=> 'Remember',
	],
	'captcha' =>
	[
		'invalid'		=> 'Incorrect spam challenge response.'
	],
];
