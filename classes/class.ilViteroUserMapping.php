<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * 
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroUserMapping.php 32432 2012-01-02 12:00:13Z smeyer $
 */
class ilViteroUserMapping
{

	/**
	 * Map user id with vitero user id
	 * @param int $a_user_id
	 * @param int $a_vuser_id 
	 */
	public function map($a_user_id, $a_vuser_id)
	{
		global $ilDB;

		$query = 'INSERT INTO rep_robj_xvit_umap (iuid,vuid) '.
			'VALUES( '.
			$ilDB->quote($a_user_id,'integer').', '.
			$ilDB->quote($a_vuser_id,'integer').' '.
			')';
		$ilDB->manipulate($query);
		return true;
	}

	public function unmap($a_user_id)
	{
		global $ilDB;

		$query = 'DELETE FROM rep_robj_xvit_umap '.
			'WHERE iuid = '.$ilDB->quote($a_user_id,'integer');
		$ilDB->manipulate($query);
		return true;
	}

	public function deleteByViteroUserId($a_vuid)
	{
		global $ilDB;

		$query = 'DELETE FROM rep_robj_xvit_umap '.
			'WHERE vuid = '.$ilDB->quote($a_vuid,'integer');
		$ilDB->manipulate($query);
	}

	public function deleteAll()
	{
		global $ilDB;

		$query = 'DELETE FROM rep_robj_xvit_umap';
		$ilDB->manipulate($query);
		return true;
	}

	/**
	 * get vitero user id
	 * @global ilDB $ilDB
	 * @param int $a_user_id
	 * @return int
	 */
	public function getVUserId($a_user_id)
	{
		global $ilDB;

		$query = 'SELECT * FROM rep_robj_xvit_umap '.
			'WHERE iuid = '.$ilDB->quote($a_user_id,'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->vuid;
		}
		return 0;
	}

}
?>
