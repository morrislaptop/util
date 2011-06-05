Debug MySQL Source
==================

Determine where those pesky queries are coming from...

Database Config
---------------

	var $default = array(
		'datasource' => 'Util.DebugMysqlSource',
		'driver' => null,
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'tagandwin',
		'prefix' => '',
	);
	
SQL Dumping
-----------

	<?php echo $this->element('sql_dump', array('plugin' => 'util')); ?>