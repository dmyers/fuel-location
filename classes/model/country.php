<?php

namespace Location;

class Model_Country extends \Orm\Model
{
	protected static $_table_name = 'location_countries';

	protected static $_properties = array(
		'id',
		'code',
		'name',
	);

	protected static $_has_many = array(
		'states' => array(
			'model_to' => 'Model_State',
			'key_from' => 'id',
			'key_to'   => 'country_id',
		),
	);
}