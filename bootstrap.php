<?php

Autoloader::add_core_namespace('Cities');

Autoloader::add_classes(array(
	'Cities\\Model_Country' => __DIR__.'/classes/model/country.php',
	'Cities\\Model_State'   => __DIR__.'/classes/model/State.php',
	'Cities\\Model_City'    => __DIR__.'/classes/model/City.php',
));
