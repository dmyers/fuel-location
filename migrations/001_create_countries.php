<?php

namespace Fuel\Migrations;

class Create_Countries
{
	function up()
	{
		\DBUtil::create_table('countries', array(
			'id' => array(
				'type'           => 'int',
				'constraint'     => 10,
				'auto_increment' => true,
			),
			'code' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
			'name' => array(
				'type'       => 'varchar',
				'constraint' => 255,
			),
		), array('id'));

		\DBUtil::create_index('countries', 'code', 'code');
	}

	function down()
	{
		\DBUtil::drop_table('countries');
	}
}