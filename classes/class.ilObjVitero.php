<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");

/**
* Application class for vitero repository object.
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
*
* $Id: class.ilObjVitero.php 56608 2014-12-19 10:11:57Z fwolf $
*/
class ilObjVitero extends ilObjectPlugin
{
	const MEMBER = 1;
	const ADMIN = 2;

	private $vgroup_id = 0;

	private $local_roles = NULL;

	/**
	* Constructor
	*
	* @access	public
	*/
	public function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}

	public function initDefaultRoles()
	{
		include_once './Services/AccessControl/classes/class.ilObjRole.php';
		$role = ilObjRole::createDefaultRole(
			'il_xvit_admin_'.$this->getRefId(),
			"Admin of vitero obj_no.".$this->getId(),
			'il_xvit_admin',
			$this->getRefId()
		);

		$role = ilObjRole::createDefaultRole(
			'il_xvit_member_'.$this->getRefId(),
			"Member of vitero obj_no.".$this->getId(),
			'il_xvit_member',
			$this->getRefId()
		);


		parent::initDefaultRoles();
	}


	public function setVGroupId($a_id)
	{
		$this->vgroup_id = $a_id;
	}

	public function getVGroupId()
	{
		return $this->vgroup_id;
	}

	
	/**
	* Get type.
	*/
	public final function initType()
	{
		$this->setType("xvit");
	}
	
	/**
	* Create object
	*/
	public function doCreate()
	{
		global $ilDB;
		
		$ilDB->manipulate(
			'INSERT INTO rep_robj_xvit_data (obj_id, vgroup_id)
				VALUES ( '.
					$ilDB->quote($this->getId(), "integer").', '.
					$ilDB->quote($this->getVGroupId(),'integer').
				')'
		);
	}
	
	/**
	* Read data from db
	*/
	public function doRead()
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT * FROM rep_robj_xvit_data ".
			" WHERE obj_id = ".$ilDB->quote($this->getId(), "integer")
		);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->setVGroupId($rec['vgroup_id']);
		}
	}
	
	/**
	* Update data
	*/
	public function doUpdate()
	{
		global $ilDB;
		
		$ilDB->manipulate(
			"UPDATE rep_robj_xvit_data SET ".
			" vgroup_id = ".$ilDB->quote($this->getVGroupId(), "integer")." ".
			" WHERE obj_id = ".$ilDB->quote($this->getId(), "integer")
		);
		try {
			$con = new ilViteroGroupSoapConnector();
			$vgrp = new ilViteroGroupSoap();
			$vgrp->id = $this->getVGroupId();
			$vgrp->name = $this->getTitle().'_'.$this->getId();
			$con->update($vgrp);
		}
		catch(ilViteroConnectorException $e)
		{
			$GLOBALS['ilLog']->write(__METHOD__.': Update vitero group failed with message code '.$e->getCode());
		}
	}
	
	/**
	* Delete data from db
	*/
	public function doDelete()
	{
		global $ilDB;
		
		$ilDB->manipulate("DELETE FROM rep_robj_xvit_data WHERE ".
			" obj_id = ".$ilDB->quote($this->getId(), "integer")
			);

		try {
			$con = new ilViteroGroupSoapConnector();
			$vgrp = new ilViteroGroupSoap();
			$vgrp->setGroupId($this->getVGroupId());
			$con->delete($vgrp);
		}
		catch(ilViteroConnectorException $e)
		{
			$GLOBALS['ilLog']->write(__METHOD__.': Delete vitero group failed with message code '.$e->getCode());
		}
	}

	public function doCloneObject($new_obj, $a_target_id, $a_copy_id)
	{
		$this->doClone($new_obj, $a_target_id, $a_copy_id);
	}
	
	/**
	* Do Cloning
	*/
	public function doClone($new_obj,$a_target_id,$a_copy_id)
	{
		global $ilDB, $ilUser;

		$GLOBALS['ilLog']->write(__METHOD__.': Start cloning');

		$new_obj->update();
		try {
			$new_obj->initVitero();
			$new_obj->addParticipants(array($ilUser->getId()), ilObjVitero::ADMIN);
			$booking_connector = new ilViteroBookingSoapConnector();
			$booking_connector->copyBookings($this->getVGroupId(), $new_obj->getVGroupId());
		}
		catch(ilViteroConnectorException $e) {
			$GLOBALS['ilLog']->write(__METHOD__.': Init vitero group failed with message code '.$e->getCode());
		}
	}

	/**
	 * Init vitero group
	 */
	public function initVitero()
	{
		try
		{
			$con = new ilViteroGroupSoapConnector();
			$vgrp = new ilViteroGroupSoap();
			$vgrp->initCustomer();
			$vgrp->setName($this->getTitle().'_'.$this->getId());
			$vg_id = $con->create($vgrp);
			$this->setVGroupId($vg_id);
			$this->doUpdate();
		}
		catch(ilViteroConnectorException $e)
		{
			throw $e;
		}
	}

	public function initAppointment(ilViteroRoom $room)
	{
		try {
			$con = new ilViteroBookingSoapConnector();
			$con->create($room, $this->getVGroupId());
		}
		catch(ilViteroConnectorException $e)
		{
			throw $e;
		}
	}

	public function addParticipants($a_user_ids, $a_type)
	{
		global $rbacadmin;

		foreach((array) $a_user_ids as $user_id)
		{
			switch($a_type)
			{
				case self::ADMIN:
					$admin = $this->getDefaultAdminRole();
					if($admin)
					{
						$rbacadmin->assignUser(
							$admin,
							$user_id
						);
					}
					break;

				case self::MEMBER:
					$member = $this->getDefaultMemberRole();
					if($member)
					{
						$rbacadmin->assignUser(
							$member,
							$user_id
						);
					}
					break;

				default:
					throw new InvalidArgumentException(
						'Invalid role type given'
					);


			}
		}
		return true;
	}

	public function getDefaultAdminRole()
	{
		$local_roles = $this->getLocalRoles();

		if(isset($local_roles['il_xvit_admin_'.$this->getRefId()]))
		{
			return $local_roles['il_xvit_admin_'.$this->getRefId()];
		}
		return 0;
	}

	public function getDefaultMemberRole()
	{
		$local_roles = $this->getLocalRoles();

		if(isset($local_roles['il_xvit_member_'.$this->getRefId()]))
		{
			return $local_roles['il_xvit_member_'.$this->getRefId()];
		}
		return 0;
	}

	/**
	* get ALL local roles of group, also those created and defined afterwards
	* only fetch data once from database. info is stored in object variable
	* @access	public
	* @return	return array [title|id] of roles...
	*/
	protected function getLocalRoles($a_translate = false)
	{
		global $rbacadmin,$rbacreview;

		if(!$this->local_roles)
		{
			$this->local_roles = array();
			$role_arr  = $rbacreview->getRolesOfRoleFolder($this->getRefId());

			foreach ($role_arr as $role_id)
			{
				if ($rbacreview->isAssignable($role_id,$this->getRefId()) == true)
				{
					$this->local_roles[ilObject::_lookupTitle($role_id)] = $role_id;
				}
			}
		}
		return $this->local_roles;
	}

	public static function handleDeletedUsers()
	{
		global $ilDB;

		$query = 'SELECT DISTINCT(vuid) FROM rep_robj_xvit_umap '.
			'LEFT JOIN usr_data ON iuid = usr_id '.
			'WHERE usr_id IS NULL '.
			'GROUP BY vuid ';

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			try {
				$user_service = new ilViteroUserSoapConnector();
				$user_service->deleteUser($row->vuid);
			}
			catch(ilViteroConnectorException $e)
			{
				;
			}
			$umap = new ilViteroUserMapping();
			$umap->deleteByViteroUserId($row->vuid);
		}
	}

	public function checkInit()
	{
		if($this->getVGroupId())
		{
			return true;
		}
		$this->initVitero(false);
	}

	public static function handleDeletedGroups()
	{
		global $ilDB;

		$query = 'SELECT child, obd.obj_id FROM tree '.
			'JOIN object_reference obr ON child = ref_id '.
			'JOIN object_data obd ON obr.obj_id = obd.obj_id '.
			'WHERE type = '.$ilDB->quote('xvit','text').' '.
			'AND tree < '.$ilDB->quote(0,'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if(!ilObject::_hasUntrashedReference($row->obj_id))
			{
				try {

					$vgroup_id = ilObjVitero::lookupVGroupId($row->obj_id);
					if(!$vgroup_id)
					{
						continue;
					}

					$start = new ilDate(time(),IL_CAL_UNIX);
					$end = clone $start;
					$start->increment(IL_CAL_YEAR,-2);
					$end->increment(IL_CAL_YEAR,2);

					$booking_service = new ilViteroBookingSoapConnector();
					$books = $booking_service->getByGroupAndDate($vgroup_id, $start, $end);


					if(is_object($books->booking))
					{
						try {
							$booking_service->deleteBooking($books->booking->bookingid);
						}
						catch(ilViteroConnectorException $e)
						{
							$GLOBALS['ilLog']->write(
								__METHOD__.': Deleting deprecated booking failed with message: ' .
								$e->getMessage()
							);
						}
					}
					if(is_array($books->booking))
					{
						foreach((array) $books->booking as $book)
						{
							try {
								$booking_service->deleteBooking($book->bookingid);
							}
							catch(ilViteroConnectorException $e)
							{
								$GLOBALS['ilLog']->write(
									__METHOD__.': Deleting deprecated booking failed with message: ' .
									$e->getMessage()
								);
							}
						}

					}

				}
				catch(ilViteroConnectorException $e)
				{
					$GLOBALS['ilLog']->write(
						__METHOD__.': Cannot read bookings of group "' .$vgroup_id.'": '.
						$e->getMessage()
					);
				}
			}

			// Delete group
			try {
				$groups = new ilViteroGroupSoapConnector();
				$groupDefinition  = new ilViteroGroupSoap();
				$groupDefinition->groupid = $vgroup_id;
				$groups->delete($groupDefinition);

				// Update vgroup id
				$query = 'UPDATE rep_robj_xvit_data '.
					'SET vgroup_id = 0 '.
					'WHERE obj_id = '.$ilDB->quote($row->obj_id,'integer');
				$ilDB->manipulate($query);
			}
			catch(ilViteroConnectorException $e)
			{
				$GLOBALS['ilLog']->write(
					__METHOD__ . ': Delete group failed: "' . $vgroup_id . '": ' .
					$e->getMessage()
				);
			}

		}
	}

	/**
	 * Lookup vgroup id
	 * @global <type> $ilDB
	 * @param <type> $a_obj_id
	 * @return <type>
	 */
	public static function lookupVGroupId($a_obj_id)
	{
		global $ilDB;

		$query = 'SELECT * FROM rep_robj_xvit_data WHERE '.
			'obj_id = '.$ilDB->quote($a_obj_id,'integer').' ';
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->vgroup_id;
		}
		return 0;
	}

	public static function lookupObjIdByGroupId($a_group_id)
	{
		global $ilDB;

		$query = 'SELECT obj_id FROM rep_robj_xvit_data '.
			'WHERE vgroup_id = '.$ilDB->quote($a_group_id,'integer').' ';
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->obj_id;
		}
		return 0;
	}
}
?>
