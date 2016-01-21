<#1>
<?php

	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'is_online' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
		)
	);

	$ilDB->createTable("rep_robj_xvit_data", $fields);
	$ilDB->addPrimaryKey("rep_robj_xvit_data", array("obj_id"));
?>
<#2>
<?php
	$ilDB->dropTableColumn('rep_robj_xvit_data','is_online');
?>
<#3>
<?php
	if(!$ilDB->tableColumnExists('rep_robj_xvit_data','vgroup_id'))
	{
		$ilDB->addTableColumn(
			'rep_robj_xvit_data',
			'vgroup_id',
			array(
				"type" => "integer",
				'length' => 4,
				"notnull" => false
			)
		);
	}
?>
<#4>
<?php

	if(!$ilDB->tableExists('rep_robj_xvit_umap'))
	{
		$fields = array(
			'iuid'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
			'vuid'   => array ('type' => 'integer', 'length' => 4, 'notnull' => true)
		);
	}

	$ilDB->createTable('rep_robj_xvit_umap', $fields);
	$ilDB->addPrimaryKey('rep_robj_xvit_umap', array('iuid','vuid'));
?>
<#5>
<?php

	$query = 'SELECT ops_id FROM rbac_operations '.
		'WHERE '.$ilDB->in(
			'operation',
			array(
				'visible',
				'read',
				'write',
				'delete',
				'edit_permission'
			),
			false,
			'text'
		);
	$res = $ilDB->query($query);
	$ops = array();
	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	{
		$ops[$row->ops_id] = $operation;
	}

	include_once ("./Services/AccessControl/classes/class.ilObjRoleTemplate.php");
	$roleObj = new ilObjRoleTemplate();
	$roleObj->setTitle('il_xvit_admin');
	$roleObj->setDescription('Administrator template for vitero groups');
	$roleObj->create();

	$GLOBALS['rbacadmin']->assignRoleToFolder($roleObj->getId(),ROLE_FOLDER_ID,'n');

	$GLOBALS['rbacadmin']->setRolePermission(
		$roleObj->getId(),
		'xvit',
		array_keys($ops),
		ROLE_FOLDER_ID
	);

?>
<#6>
<?php
	$query = 'SELECT ops_id FROM rbac_operations '.
		'WHERE '.$ilDB->in(
			'operation',
			array(
				'visible',
				'read',
			),
			false,
			'text'
		);
	$res = $ilDB->query($query);
	$ops = array();
	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	{
		$ops[$row->ops_id] = $operation;
	}

	include_once ("./Services/AccessControl/classes/class.ilObjRoleTemplate.php");
	$roleObj = new ilObjRoleTemplate();
	$roleObj->setTitle('il_xvit_member');
	$roleObj->setDescription('Member template for vitero groups');
	$roleObj->create();

	$GLOBALS['rbacadmin']->assignRoleToFolder($roleObj->getId(),ROLE_FOLDER_ID,'n');

	$GLOBALS['rbacadmin']->setRolePermission(
		$roleObj->getId(),
		'xvit',
		array_keys($ops),
		ROLE_FOLDER_ID
	);
?>

<#7>
<?php

	$ilDB->createTable('rep_robj_xvit_excl',array(
		'excl_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'book_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'excl_date'	=> array(
			'type'	=> 'date',
			'notnull' => FALSE,
		)
	));

	$ilDB->addPrimaryKey('rep_robj_xvit_excl',array('excl_id'));
	$ilDB->addIndex('rep_robj_xvit_excl',array('book_id'),'i1');
	$ilDB->createSequence('rep_robj_xvit_excl');
?>
<#8>
<?php

	$ilDB->createTable('rep_robj_xvit_locked',array(
		'usr_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'vgroup_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'locked'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => TRUE,
		)
	));

	$ilDB->addPrimaryKey('rep_robj_xvit_locked',array('usr_id','vgroup_id'));
?>