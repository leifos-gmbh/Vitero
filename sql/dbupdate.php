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
if(!$ilDB->tableExists('rep_robj_xvit_locked'))
{
	$ilDB->createTable('rep_robj_xvit_locked', array(
		   'usr_id' => array(
			   'type' => 'integer',
			   'length' => 4,
			   'notnull' => TRUE
		   ),
		   'vgroup_id' => array(
			   'type' => 'integer',
			   'length' => 4,
			   'notnull' => TRUE
		   ),
		   'locked' => array(
			   'type' => 'integer',
			   'length' => 1,
			   'notnull' => TRUE,
		   )
	   )
	);

	$ilDB->addPrimaryKey('rep_robj_xvit_locked', array('usr_id', 'vgroup_id'));
}
?>
<#9>
<?php
if(!$ilDB->tableExists('rep_robj_xvit_smap'))
{
	$ilDB->createTable('rep_robj_xvit_smap',array(
		'usr_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'vsession'	=> array(
			'type'	=> 'text',
			'length'=> 128,
			'notnull' => TRUE
		),
		'expirationdate'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE,
		),
		'vtype'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => TRUE,
		)
	));

	$ilDB->addPrimaryKey('rep_robj_xvit_smap',array('usr_id','vsession'));
}

?>
<#10>
<?php
if(!$ilDB->tableExists('rep_robj_xvit_codes'))
{
	$ilDB->createTable('rep_robj_xvit_codes',array(
		'vgroup_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'booking_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'code'	=> array(
			'type'	=> 'text',
			'length' => 8,
			'notnull' => FALSE,
		)
	));
}
$ilDB->addPrimaryKey('rep_robj_xvit_codes',array('vgroup_id','booking_id'));
?>

<#11>
<?php
if(!$ilDB->tableExists('rep_robj_xvit_webcodes'))
{
	$ilDB->createTable('rep_robj_xvit_webcodes',array(
		'vgroup_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'booking_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'webcode'	=> array(
			'type'	=> 'text',
			'length' => 8,
			'notnull' => FALSE,
		),
		'browserurl'	=> array(
			'type'	=> 'text',
			'length' => 512,
			'notnull' => FALSE,
		),
		'appurl'	=> array(
			'type'	=> 'text',
			'length' => 512,
			'notnull' => FALSE,
		),

	));
}
$ilDB->addPrimaryKey('rep_robj_xvit_webcodes',array('vgroup_id','booking_id'));
?>
<#12>
<?php
include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
$edit_operation_id = ilDBUpdateNewObjectType::getCustomRBACOperationId("edit_learning_progress");
$write_operation_id = ilDBUpdateNewObjectType::getCustomRBACOperationId('write');

if($edit_operation_id)
{
	$lp_types = array("xvit");

	foreach($lp_types as $lp_type)
	{
		$lp_type_id = ilDBUpdateNewObjectType::getObjectTypeId($lp_type);

		if($lp_type_id)
		{
			ilDBUpdateNewObjectType::addRBACOperation($lp_type_id, $edit_operation_id);
			ilDBUpdateNewObjectType::cloneOperation($lp_type, $write_operation_id, $edit_operation_id);
		}
	}
}
?>
<#13>
<?php
if(!$ilDB->tableExists('rep_robj_xvit_lp'))
{
	$ilDB->createTable('rep_robj_xvit_lp',array(
		'obj_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'active'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => FALSE
		),
		'min_percent'	=> array(
			'type'	=> 'integer',
			'length' => 4,
			'notnull' => FALSE,
		),
		'mode_multi'	=> array(
			'type'	=> 'integer',
			'length' => 4,
			'notnull' => FALSE,
		),
		'min_sessions'	=> array(
			'type'	=> 'integer',
			'length' => 4,
			'notnull' => FALSE,
		),

	));
}
$ilDB->addPrimaryKey('rep_robj_xvit_lp',array('obj_id'));
?>
<#14>
<?php
if(!$ilDB->tableExists('rep_robj_xvit_recs'))
{
	$ilDB->createTable('rep_robj_xvit_recs', array(
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'obj_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'recording_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'percent'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		)
	));

	$ilDB->addPrimaryKey('rep_robj_xvit_recs',array('user_id','obj_id','recording_id'));
}
?>
<#15>
<?php
if($ilDB->tableExists('rep_robj_xvit_recs'))
{
	if($ilDB->tableColumnExists('rep_robj_xvit_recs','percent'))
	{
		$ilDB->renameTableColumn('rep_robj_xvit_recs','percent', 'percentage');
	}
}
?>
<#16>
<?php
if(!$ilDB->tableExists("rep_robj_xvit_date"))
{
	$ilDB->createTable('rep_robj_xvit_date', array(
		'last_sync'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		)
	));
}
?>


