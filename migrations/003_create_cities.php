<?php

namespace Fuel\Migrations;

class Create_cities
{
	function up()
	{
		\DBUtil::create_table('cities', array(
			'id' => array(
				'type'       => 'int',
				'constraint' => 10,
			),
			'country_code' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
			'country_id' => array(
				'type'       => 'int',
				'constraint' => 10,
			),
			'state_code' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
			'state_id' => array(
				'type'       => 'int',
				'constraint' => 10,
			),
			'name' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
			'slug' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
		), array('id'));
		
		\DBUtil::create_index('cities', 'slug', 'slug');
	}

	function down()
	{
		\DBUtil::drop_table('cities');
	}
}