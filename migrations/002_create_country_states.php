<?php

namespace Fuel\Migrations;

class Create_Country_States
{
	function up()
	{
		\DBUtil::create_table('country_states', array(
			'id' => array(
				'type'           => 'int',
				'constraint'     => 10,
				'auto_increment' => true,
			),
			'code' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
			'country_code' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
			'country_id' => array(
				'type'       => 'int',
				'constraint' => 10,
			),
			'name' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
		), array('id'));

		\DBUtil::create_index('country_states', array('country_code', 'code'), 'code');
	}

	function down()
	{
		\DBUtil::drop_table('country_states');
	}
}