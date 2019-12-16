<?php
/**
 * Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE
 *
 * @author Jesús López Reyes <lopez@leifos.com>
 */
class ilViteroLearningProgress
{
	const PASSED = "passed";
	const NOT_PASSED = "not passed";
	const CRON_PLUGIN_ID = "xvitc";

	/**
	 * @var ilObjVitero
	 */
	protected $vitero_object;

	/**
	 * @var ilViteroUserMapping
	 */
	protected $user_mapping;

	/**
	 * @var null | \ilLogger
	 */
	private $logger = null;


	public function __construct()
	{
		global $DIC;

		$this->logger = $DIC->logger()->xvit();

		$this->vitero_object = new ilObjVitero();
		$this->user_mapping = new ilViteroUserMapping();
	}

	/**
	 * Booking in vitero = Appointment in ILIAS
	 * @param int vitero group id $a_vitero_group_id
	 * @throws ilDateTimeException
	 */
	public function updateLearningProgress(int $a_vgroup_id = 0)
	{
		$statistic_connector = new ilViteroStatisticSoapConnector();
		$booking_connector = new ilViteroBookingSoapConnector();

		$settings = new ilViteroSettings();
		$customer_id = $settings->getCustomer();

		$time_slot = $this->getTimeSlotToGetViteroRecordings();

		$session_and_user_recordings = $statistic_connector->getSessionAndUserRecordingsByTimeSlot(
			$time_slot['start'],
			$time_slot['end'],
			$customer_id,
			$a_vgroup_id
		);

		$this->logger->dump($time_slot);
		$this->logger->dump($customer_id);
		$this->logger->dump($a_vgroup_id);
		$this->logger->dump($session_and_user_recordings);

		if(is_object($session_and_user_recordings->sessionrecording))
		{
			$session_and_user_recordings = array($session_and_user_recordings->sessionrecording);
		}
		else if(is_array($session_and_user_recordings->sessionrecording))
		{
			$session_and_user_recordings = $session_and_user_recordings->sessionrecording;
		}

		// Notice: The booking id here is not a real booking id because vitero needs this to keep backward compatibility.
		// Notice: The booking id is a bookingTimeId and we can get a booking obj. via getBookingTimeId(bookingTimeId)
		foreach ($session_and_user_recordings as $session_user_recording)
		{
			$ilias_object_id = ilObjVitero::lookupObjIdByGroupId($session_user_recording->groupid);

			//Omit this group id if there is not an ILIAS vitero session assigned.
			if($ilias_object_id == 0) {
				continue;
			}

			$this->vitero_object->setId($ilias_object_id);

			$this->vitero_object->readLearningProgressSettings();

			if($this->vitero_object->isLearningProgressActive())
			{
				$booking = $booking_connector->getBookingByBookingTimeId($session_user_recording->bookingid);

				$user_recording_id = $session_user_recording->userrecording->userrecordingid;

				if($session_user_recording->sessionend >= $booking->booking->start)
				{
					$user_percent_attended = 0;

					//parse vitero string dates to ilDateTime
					$booking_start = ilViteroUtils::parseSoapDate($booking->booking->start)->getUnixTime();
					$booking_end = ilViteroUtils::parseSoapDate($booking->booking->end)->getUnixTime();

					$booking_duration_seconds = $booking_end - $booking_start;

					$this->logger->debug('Booking duration: ' . $booking_duration_seconds);

					$user_start = ilViteroUtils::parseSoapDate($session_user_recording->sessionstart)->getUnixTime();
					$user_end = ilViteroUtils::parseSoapDate($session_user_recording->sessionend)->getUnixTime();

					//get the effective start and end
					$real_start = max($booking_start, $user_start);
					$real_end = min($booking_end, $user_end);

					//get the effective time spent by the user in the booking session
					$user_time_attended = $real_end - $real_start;

					$this->logger->debug('Spent time is: ' . $user_time_attended);

					//get percentage of the effective time spent rounded always down only if user has effective time.
					if($user_time_attended > 0){
						$user_percent_attended = floor($user_time_attended * 100 / $booking_duration_seconds);
					}

					$this->logger->debug('Percent attended: ' . $user_percent_attended);

					$user_id = $this->user_mapping->getIUserId($session_user_recording->userrecording->userid);

					//if user mapped properly
					if($user_id)
					{
						$this->updateUserRecordingAttendance($ilias_object_id, $user_id, $user_recording_id, $user_percent_attended);
					}
				}
			}

		}
		ilLPStatusWrapper::_refreshStatus($ilias_object_id);
		ilViteroUtils::updateLastSyncDate();

	}

	/**
	 * @param $a_completed_sessions
	 * @return array
	 */
	public function getUsersStatus($a_completed_sessions)
	{
		$users_status = array();

		foreach ($a_completed_sessions as $user_id => $total_passed)
		{
			$status = self::NOT_PASSED;
			if($this->vitero_object->isLearningProgressModeMultiActive())
			{
				if($total_passed >= $this->vitero_object->getLearningProgressMinSessions())
				{
					$status = self::PASSED;
				}
			}
			else if($total_passed > 0)
			{
				$status = self::PASSED;
			}

			$users_status[] = array(
				"user_id"   => $user_id,
				"obj_id"    => $this->vitero_object->getId(),
				"status"    => $status
			);
		}

		return $users_status;
	}

	/**
	 * @param $a_recording_id
	 * @param $a_recording_user_id
	 * @param $user_percent_attended
	 */
	public function updateUserRecordingAttendance($a_ilias_object_id, $a_user_id, $a_userrecording_id, $a_user_percent_attended)
	{
		if($this->isNotUserRecordingStoredInDB($a_userrecording_id))
		{
			$this->insertUserRecording($a_ilias_object_id, $a_userrecording_id, $a_user_id, $a_user_percent_attended);
		}

	}

	/**
	 * @param $a_recording_id
	 * @return bool
	 * @throws ilDatabaseException
	 */
	protected function isNotUserRecordingStoredInDB($a_recording_id)
	{
		global $DIC;

		$db = $DIC->database();

		$query = 'SELECT user_id FROM rep_robj_xvit_recs'.
			' WHERE recording_id = '.$db->quote($a_recording_id,'integer');

		$res = $db->query($query);

		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $a_recording_id
	 * @param $a_user_id
	 * @param $a_user_percent_attended
	 */
	protected function insertUserRecording($a_ilias_object_id, $a_userrecording_id, $a_user_id, $a_user_percent_attended)
	{
		global $DIC;

		$db = $DIC->database();

		$sql = 'INSERT INTO rep_robj_xvit_recs (user_id,obj_id,recording_id,percentage) ' .
			'VALUES(' .
			$db->quote($a_user_id, 'integer') . ', ' .
			$db->quote($a_ilias_object_id, 'integer') . ', ' .
			$db->quote($a_userrecording_id, 'integer') . ', ' .
			$db->quote($a_user_percent_attended,'integer') .
			')';

		$db->manipulate($sql);
	}

	/**
	 * Gets an array with starting date and ending date
	 * @return array
	 * @throws ilDatabaseException
	 * @throws ilDateTimeException
	 */
	public function getTimeSlotToGetViteroRecordings()
	{
		$last_cron_ejecution_date = ilViteroUtils::getLastSyncDate();
		// @fixme
		$last_cron_ejecution_date = 0;

		//first cron execution will start dealing with events from 5 years ago. Later executions will start from current date - 1 day
		if($last_cron_ejecution_date > 0)
		{
			$start_range = new ilDateTime($last_cron_ejecution_date,IL_CAL_UNIX);
			$start_range->increment(IL_CAL_DAY,-1);
		}
		else
		{
			$start_range = new ilDateTime(time(),IL_CAL_UNIX);
			$start_range->increment(IL_CAL_YEAR,-5);
		}

		$start_unix = $start_range->getUnixTime();
		$start_str = date('YmtHi',$start_unix);

		$end_range = new ilDateTime(time(),IL_CAL_UNIX);
		$end_range->increment(IL_CAL_YEAR,1);
		$end_unix = $end_range->getUnixTime();
		$end_str = date('YmtHi',$end_unix);

		return array(
			"start" => $start_str,
			"end" => $end_str
		);
	}
}