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
 * TODO(Next patch will fix this) DRY for the Query getLPInProgress getLTCompleted etc... 99% duplicated
* Application class for vitero repository object.
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
*
* $Id: class.ilObjVitero.php 56608 2014-12-19 10:11:57Z fwolf $
*/
class ilObjVitero extends ilObjectPlugin implements ilLPStatusPluginInterface
{
	const MEMBER = 1;
	const ADMIN = 2;

	const LP_MODE_ONE = 0;
	const LP_MODE_MULTI = 1;

	private $vgroup_id = 0;

	private $local_roles = NULL;

	protected $learning_progress = false;
	protected $learning_progress_min_percentage;
	protected $learning_progress_mode_multi = false;
	protected $learning_progress_min_sessions;
	protected $is_learning_progress_stored = false; //TODO maybe this var and setters/getters should be renamed to something using the word settings.

	/**
	 * @var null | \ilLogger
	 */
	private $logger = null;

	/**
	* Constructor
	*
	* @access	public
	*/
	public function __construct($a_ref_id = 0)
	{
		global $DIC;

		parent::__construct($a_ref_id);
		$this->logger = $DIC->logger()->xvit();
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

	public function doCloneObject($new_obj, $a_target_id, $a_copy_id = 0)
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

	/**
	 * @param ilViteroRoom $room
	 * @param bool $a_anonymous_access
	 * @param bool $a_webaccess_codes
	 * @throws ilViteroConnectorException
	 */
	public function initAppointment(ilViteroRoom $room, $a_anonymous_access = false, $a_webaccess_codes = false)
	{
		try {
			$con = new ilViteroBookingSoapConnector();
			$booking_id = $con->create($room, $this->getVGroupId());
			if($a_anonymous_access)
			{
				$session = new ilViteroSessionCodeSoapConnector();
				$session->createBookingSessionCode($booking_id, $this->getVGroupId());
			}
			if($a_webaccess_codes)
			{
				$this->handleMobileAccess($a_webaccess_codes, $booking_id);
			}
		}
		catch(ilViteroConnectorException $e)
		{
			throw $e;
		}
	}

	/**
	 * Handle mobile access
	 * @param bool $a_is_enabled
	 * @param int $a_booking_id
	 */
	public function handleMobileAccess($a_is_enabled, $a_booking_id)
	{
		ilLoggerFactory::getLogger('xvit')->debug('Handling mobile access settings.');
		ilLoggerFactory::getLogger('xvit')->dump($a_is_enabled);

		if(!ilViteroSettings::getInstance()->isMobileAccessEnabled()) {
			ilLoggerFactory::getLogger('xvit')->debug('Disabled by global configuration.');
			return false;
		}

		$mobile_access = new ilViteroBookingWebCode(
			$this->getVGroupId(),
			$a_booking_id
		);
		ilLoggerFactory::getLogger('xvit')->dump($mobile_access->exists());
		if($mobile_access->exists() && !$a_is_enabled)
		{
			// delete session code manually
			$mobile_access->delete();
		}
		elseif(!$mobile_access->exists() && $a_is_enabled)
		{
			// add session code
			try {
				$session_connect = new ilViteroSessionCodeSoapConnector();
				$session_connect->createWebAccessSessionCode(
					$this->getVGroupId(),
					$a_booking_id
				);
			}
			catch(ilViteroConnectorException $exception) {
				ilLoggerFactory::getLogger('xvit')->error('Creating webaccess session code failed with message code: ' . $exception->getCode());
				ilUtil::sendFailure('Creating webaccess session code failed with message code: ' . $exception->getCode(),true);
			}
		}
		return true;
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
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
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
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
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
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
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
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			return $row->obj_id;
		}
		return 0;
	}

	//LEARNING PROGRESS SETTINGS
	public function setLearningProgress($learning_progress)
	{
		$this->learning_progress = (bool)$learning_progress;
	}

	public function isLearningProgressActive()
	{
		return $this->learning_progress;
	}

	public function setLearningProgressMinPercentage($percentage)
	{
		$this->learning_progress_min_percentage = $percentage;
	}

	public function getLearningProgressMinPercentage()
	{
		return $this->learning_progress_min_percentage;
	}

	public function setLearningProgressModeMulti($is_multi)
	{
		$this->learning_progress_mode_multi = (bool)$is_multi;
	}

	public function isLearningProgressModeMultiActive()
	{
		return $this->learning_progress_mode_multi;
	}

	public function setLearningProgressMinSessions($num_sessions)
	{
		$this->learning_progress_min_sessions = (int)$num_sessions;
	}

	public function getLearningProgressMinSessions()
	{
		return $this->learning_progress_min_sessions;
	}

	public function saveLearningProgressData()
	{
		if($this->is_learning_progress_stored) {
			$this->updateLearningProgress();
		} else {
			$this->insertLearningProgress();
		}

		ilLPStatusWrapper::_refreshStatus($this->getId());
	}

	protected function updateLearningProgress()
	{
		global $ilDB;

		$query = "UPDATE rep_robj_xvit_lp SET" .
			" active = " . $ilDB->quote($this->isLearningProgressActive(), "integer") . ", " .
			" min_percent = " . $ilDB->quote($this->getLearningProgressMinPercentage(), "integer") . ", " .
			" mode_multi = " . $ilDB->quote($this->isLearningProgressModeMultiActive(), "integer") . ", " .
			" min_sessions = " . $ilDB->quote($this->getLearningProgressMinSessions(), "integer") .
			" WHERE obj_id = ".$ilDB->quote($this->getId(), "integer");

		return $ilDB->manipulate($query);
	}

	protected function insertLearningProgress()
	{
		global $ilDB;

		$query = "INSERT INTO rep_robj_xvit_lp (obj_id,active,min_percent,mode_multi,min_sessions)" .
			" VALUES(" .
			$ilDB->quote($this->getId(), "integer") . ", " .
			$ilDB->quote($this->isLearningProgressActive(), "integer") . ", " .
			$ilDB->quote($this->getLearningProgressMinPercentage(), "integer") . ", " .
			$ilDB->quote($this->isLearningProgressModeMultiActive(), "integer") . ", " .
			$ilDB->quote($this->getLearningProgressMinSessions(), "integer") .
			")";

		$affected_rows = $ilDB->manipulate($query);

		if($affected_rows > 0){
			$this->setLearningProgressStored();
		}

		return $affected_rows;
	}

	public function readLearningProgressSettings()
	{
		global $ilDB;

		$query = "SELECT * FROM rep_robj_xvit_lp WHERE obj_id =" . $ilDB->quote($this->getId(), "integer");

		$res = $ilDB->query($query);

		while ($row = $res->fetchAssoc($res))
		{
			$this->setLearningProgress($row['active']);
			$this->setLearningProgressMinPercentage($row['min_percent']);
			$this->setLearningProgressModeMulti($row['mode_multi']);
			$this->setLearningProgressMinSessions($row['min_sessions']);

			$this->setLearningProgressStored();
		}
	}

	protected function setLearningProgressStored()
	{
		$this->is_learning_progress_stored = true;
	}
	
	/**
	 * @return int total number of appointments.
	 * @throws ilDateTimeException
	 */
	public function getNumberOfAppointmentsForSession()
	{
		$start = new ilDateTime(time(),IL_CAL_UNIX);
		$end = clone $start;
		$start->increment(IL_CAL_YEAR,-5);
		$end->increment(IL_CAL_YEAR,1);

		$vitero_group_id = $this->getVGroupId();

		try {
			$con = new ilViteroBookingSoapConnector();
			$bookings = $con->getByGroupAndDate($vitero_group_id, $start, $end);
		}
		catch(Exception $e) {
			throw $e;
		}

		$booking_arr = array();
		if(is_object($bookings->booking))
		{
			$booking_arr = array($bookings->booking);
		}
		elseif(is_array($bookings->booking))
		{
			$booking_arr = $bookings->booking;
		}

		$total_appointments = 0;
		foreach($booking_arr as $booking)
		{
			//one booking can contain more than one appointment
			foreach (ilViteroUtils::calculateBookingAppointments($start, $end, $booking) as $dl) {
				$total_appointments++;
			}
		}

		return $total_appointments;
	}

	/**
	 * Get all user ids with LP status completed
	 *
	 * @return array
	 */
	public function getLPCompleted()
	{
		global $DIC;

		$db = $DIC->database();

		$this->readLearningProgressSettings();

		if( ! $this->isLearningProgressActive())
		{
			return array();
		}

		$min_percent = $this->getLearningProgressMinPercentage();
		$min_sessions_passed = $this->getLearningProgressMinSessions();

		$sql = "SELECT user_id," .
			" COUNT( CASE WHEN percentage >= " .$this->db->quote($min_percent, "integer"). " THEN 1 END) count_passed" .
			" FROM rep_robj_xvit_recs" .
			" WHERE obj_id = " . $this->db->quote($this->getId(), "integer") .
			" GROUP BY user_id";

		$this->logger->debug($sql);

		$res = $db->query($sql);

		$users_completed = array();

		while($row = $db->fetchAssoc($res))
		{
			if(
				$row['count_passed'] &&
				$row['count_passed'] >= $min_sessions_passed)
			{
				array_push($users_completed, $row['user_id']);
			}
		}

		return $users_completed;
	}

	/**
	 * Get all user ids with LP status not attempted
	 *
	 * @return array
	 */
	public function getLPNotAttempted()
	{

		//TODO members added but never entered in the vitero session
		// compare members list with lp list (getUsersAttempted)
		return array();
	}

	/**
	 * Get all user ids with LP status failed
	 *
	 * @return array
	 */
	public function getLPFailed()
	{
		//nothing to do here.
		return array();
	}

	/**
	 * Get all user ids with LP status in progress
	 *
	 * @return array
	 */
	public function getLPInProgress()
	{
		global $DIC;

		$db = $DIC->database();

		$this->readLearningProgressSettings();

		if( ! $this->isLearningProgressActive())
		{
			return array();
		}

		$min_percent = $this->getLearningProgressMinPercentage();
		$min_sessions_passed = $this->getLearningProgressMinSessions();

		$this->logger->debug('Minimum percentage required: ' . $min_percent);
		$this->logger->debug('Minimum sessions required: ' . $min_sessions_passed);

		$sql = "SELECT user_id," .
			" COUNT( CASE WHEN percentage > " .$this->db->quote($min_percent, "integer"). " THEN 1 END) count_passed" .
			" FROM rep_robj_xvit_recs" .
			" WHERE obj_id = " . $this->db->quote($this->getId(), "integer") .
			" GROUP BY user_id";

		$res = $db->query($sql);

		$users_in_progress = array();
		while($row = $db->fetchAssoc($res))
		{
			if($row['count_passed'] < $min_sessions_passed)
			{
				array_push($users_in_progress, $row['user_id']);
			}
		}

		return $users_in_progress;
	}

	/**
	 * Get current status for given user
	 *
	 * @param int $a_user_id
	 * @return int
	 */
	public function getLPStatusForUser($a_user_id)
	{
		if(in_array($a_user_id, $this->getUsersAttempted()))
		{
			if(ilLPStatus::_hasUserCompleted($this->getId(), $a_user_id))
			{
				return ilLPStatus::LP_STATUS_COMPLETED_NUM;
			}

			return ilLPStatus::LP_STATUS_IN_PROGRESS_NUM;
		}

		return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
	}

	/**
	 * Get users ids of the current vitero object.
	 * @return array
	 */
	protected function getUsersAttempted()
	{
		global $DIC;

		$db = $DIC->database();

		$sql = "SELECT user_id" .
			" FROM rep_robj_xvit_recs" .
			" WHERE obj_id = " . $this->db->quote($this->getId(), "integer") .
			" GROUP BY user_id";

		$res = $db->query($sql);

		$users_attempted = array();
		while($row = $db->fetchAssoc($res))
		{
			array_push($users_attempted, $row['user_id']);
		}

		return $users_attempted;
	}

	public function isLearningProgressAvailable()
	{
		if(ilLearningProgressAccess::checkAccess($this->getRefId())
			&& ilViteroSettings::getInstance()->isLearningProgressEnabled()
			&& ilViteroUtils::hasCustomerMonitoringMode())
		{
			return true;
		}

		return false;
	}

}
?>
