<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Handles the locked users
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroLockedUser.php 33166 2012-02-14 13:49:39Z smeyer $
 */
class ilViteroLockedUser
{

	private $usr_id = 0;
	private $vgroup_id = 0;
	private $locked = 0;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		;
	}

	/**
	 * Delete user
	 * @param int $a_usr_id
	 */
	public static function deleteUser($a_usr_id)
	{
		global $ilDB;

		$query = 'DELETE FROM rep_robj_xvit_locked '.
			'WHERE usr_id = '.$ilDB->quote($a_usr_id,'integer');
		$ilDB->manipulate($query);
	}

	/**
	 * Check if there is any locked for a group
	 * @param int $a_vgroup_id
	 */
	public static function hasLockedAccounts($a_vgroup_id)
	{
		return count(self::getLockedAccounts($a_vgroup_id));
	}

	/**
	 * Check if user is locked
	 * @param int $a_user_id
	 * @param int $a_vgroup_id
	 * @return bool
	 */
	public static function isLocked($a_user_id, $a_vgroup_id)
	{
		$locked = self::getLockedAccounts($a_vgroup_id);
		return in_array($a_user_id, (array) $locked);
	}

	/**
	 * Get locked accounts
	 * @global ilDB $ilDB
	 * @param int $a_vgroup_id
	 * @return array
	 */
	public static function getLockedAccounts($a_vgroup_id)
	{
		global $ilDB;

		$query = 'SELECT usr_id FROM rep_robj_xvit_locked '.
			'WHERE vgroup_id = '.$ilDB->quote($a_vgroup_id,'integer').' '.
			'AND locked  = '.$ilDB->quote(1,'integer');
		$res = $ilDB->query($query);

		$users = array();
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$users[] = $row->usr_id;
		}
		return $users;
	}


	/**
	 * Set user id
	 * @param int $a_usr_id
	 */
	public function setUserId($a_usr_id)
	{
		$this->usr_id = $a_usr_id;
	}

	/**
	 * Set vgroup id
	 * @param int $a_vgroup_id
	 */
	public function setVGroupId($a_vgroup_id)
	{
		$this->vgroup_id = $a_vgroup_id;
	}
	
	/**
	 * Set locked
	 * @param bool $a_locked 
	 */
	public function setLocked($a_locked) 
	{
		$this->locked = $a_locked;
	}



	/**
	 * Update usr status
	 * @global ilDB $ilDB 
	 */
	public function update()
	{
		global $ilDB;

		$ilDB->replace(
			'rep_robj_xvit_locked',
			array(
				'usr_id' => array('integer',$this->usr_id),
				'vgroup_id' => array('integer',$this->vgroup_id)
			),
			array(
				'locked' => array('integer',(int) $this->locked)
			)
		);
	}

	/**
	 * Delete entry for user
	 * @global ilDB $ilDB
	 */
	public function delete()
	{
		global $ilDB;

		$query = 'DELETE FROM rep_robj_xvit_locked '.
			'WHERE usr_id = '.$ilDB->quote($this->usr_id).' '.
			'AND vgroup_id = '.$ilDB->quote($this->vgroup_id);
		$ilDB->manipulate($query);
	}
}
?>
