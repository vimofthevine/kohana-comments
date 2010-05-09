<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'default' => array(
		'model'       => 'comment',
		'per_page'    => 10,
		'view'        => 'comments',
		'lower_limit' => 0.2,
		'upper_limit' => 0.9,
		'order'       => 'DESC',
	),
);

