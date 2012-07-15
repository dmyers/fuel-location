<?php

Autoloader::add_core_namespace('Cities');

Autoloader::add_classes(array(
	'Cities\\Model_Country'          => __DIR__.'/classes/model/country.php',
	'Cities\\Model_CountryState'     => __DIR__.'/classes/model/countrystate.php',
	'Cities\\Model_CountryStateCity' => __DIR__.'/classes/model/countrystatecity.php',
));
