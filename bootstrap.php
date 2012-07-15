<?php

Autoloader::add_core_namespace('Location');

Autoloader::add_classes(array(
	'Location\\Location'      => __DIR__.'/classes/location.php',
	'Location\\Model_Country' => __DIR__.'/classes/model/country.php',
	'Location\\Model_State'   => __DIR__.'/classes/model/State.php',
	'Location\\Model_City'    => __DIR__.'/classes/model/City.php',
));
