<?php
/**
 * TODO remove dummy array data
 * TODO type hints
 * TODO constants should be = as ilLPStatus::LP_STATUS_COMPLETED_NUM etc.
 * Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE
 *
 * @author Jesús López Reyes <lopez@leifos.com>
 */
class ilViteroLearningProgress
{
	const PASSED = "passed";
	const NOT_PASSED = "not passed";

	/**
	 * @var ilObjVitero
	 */
	protected $vitero_object;

	/**
	 * @var ilViteroUserMapping
	 */
	protected $user_mapping;

	public function __construct()
	{
		$this->vitero_object = new ilObjVitero();
		$this->user_mapping = new ilViteroUserMapping();
	}

	/**
	 * Booking in vitero = Appointment in ILIAS
	 * TODO pending real sessions in vitero platform to test this.
	 * @throws ilDateTimeException
	 */
	public function updateLearningProgress()
	{
		$statistic_connector = new ilViteroStatisticSoapConnector();
		$booking_connector = new ilViteroBookingSoapConnector();

		$settings = new ilViteroSettings();
		$customer_id = $settings->getCustomer();

		//TODO Improve this date stuff
		$start_range = new ilDateTime(time(),IL_CAL_UNIX);
		$end_range = clone $start_range;

		$start_range->increment(IL_CAL_YEAR,-5);
		$start_unix = $start_range->getUnixTime();
		$start_str = date('YmtHi',$start_unix);

		$end_range->increment(IL_CAL_YEAR,1);
		$end_unix = $end_range->getUnixTime();
		$end_str = date('YmtHi',$end_unix);

		$session_and_user_recordings = $statistic_connector->getSessionAndUserRecordingsByTimeSlot($start_str, $end_str, $customer_id);

		//Todo parse recordings (only with future events etc.)
		//$session_and_user_recordings = $this->parseRecordingsByInitialCriteria($session_and_user_recordings);

		if(is_object($session_and_user_recordings->sessionrecording))
		{
			$session_and_user_recordings = array($session_and_user_recordings->sessionrecording);
		}
		else if(is_array($session_and_user_recordings->sessionrecording))
		{
			$session_and_user_recordings = $session_and_user_recordings->sessionrecording;
		}

		//TODO: The booking id here is not a real booking id because vitero needs this to keep backward compatibility.
		//TODO: The booking id is a bookingTimeId and we can get a booking obj. via getBookingTimeId(bookingTimeId)
		foreach ($session_and_user_recordings as $session_user_recording)
		{
			$ilias_object_id = ilObjVitero::lookupObjIdByGroupId($session_user_recording->groupid);

			$this->vitero_object->setId($ilias_object_id);

			$this->vitero_object->readLearningProgressSettings();

			if($this->vitero_object->getLearningProgress())
			{
				$booking = $booking_connector->getBookingByBookingTimeId($session_user_recording->bookingid);

				$user_recording_id = $session_user_recording->userrecording->userrecordingid;

				if($session_user_recording->sessionend >= $booking->booking->start)
				{
					$user_time_attended = 0;
					$user_percent_attended = 0;

					//parse vitero string dates to ilDateTime
					$booking_start = ilViteroUtils::parseSoapDate($booking->booking->start)->getUnixTime();
					$booking_end = ilViteroUtils::parseSoapDate($booking->booking->end)->getUnixTime();

					$booking_duration_seconds = $booking_end - $booking_start;

					$user_start = ilViteroUtils::parseSoapDate($session_user_recording->sessionstart)->getUnixTime();
					$user_end = ilViteroUtils::parseSoapDate($session_user_recording->sessionend)->getUnixTime();

					//get the effective start and end
					$real_start = max($booking_start, $user_start);
					$real_end = min($booking_end, $user_end);

					//get the effective time spent by the user in the booking session
					$user_time_attended = $real_end - $real_start;

					//get percentage of the effective time spent rounded always down only if user has effective time.
					if($user_time_attended > 0){
						$user_percent_attended = floor($user_time_attended * 100 / $booking_duration_seconds);
					}

					$user_id = $this->user_mapping->getIUserId($session_user_recording->userrecording->userid);

					//if user mapped properly
					if($user_id)
					{
						$this->updateUserRecordingAttendance($ilias_object_id, $user_id, $user_recording_id, $user_percent_attended);
					}
				}

			}

			//TODO : Is this ok????
			ilLPStatusWrapper::_refreshStatus($ilias_object_id);
		}



	}

	/**
	 * Filter for sessions
	 * @param $recordings
	 * @return mixed
	 */
	public function parseRecordings($recordings)
	{
		// Return only recordings if LP is active +
		// user finished the session(userrecording->userend) +
		// session is finished(getSessionAndUserRecordingsByTimeSlotRequest->timeslotend)
		return $recordings;
	}

	/**
	 * @param $a_user_recording
	 * @param $a_session_start
	 * @param $a_session_end
	 * @return array
	 */
	public function getUserSessionsAttended($a_user_recording, $a_session_start, $a_session_end)
	{
		$user_sessions_attended = array();

		//TODO no foreach here, we have only one userrecording object per appointment.
		foreach($a_user_recording as $record)
		{
			$user_start = strtotime($record['userstart']);
			$user_end = strtotime($record['userend']);

			//time in real scheduled session time period.
			$real_start = $this->getRealStartDateTime($a_session_start, $user_start);
			$real_end   = $this->getRealEndDateTime($a_session_end, $user_end);

			$user_time_spent = $real_end - $real_start;

			$session_duration = $a_session_end - $a_session_start;

			$user_percent_attended = floor($user_time_spent * 100 / $session_duration);

			$user_id = $this->user_mapping->getIUserId($record["userid"]);
			$userrecording_id = $record['userrecordingid'];

			$user_sessions_attended[] = array(
				"userrecordingid"   => $userrecording_id,
				"userid"            => $user_id,
				"percentage"        => $user_percent_attended
			);
		//$user_sessions_attended[$user_id][$recording_id] = $user_sessions_attended[$user_id][$recording_id] + $user_time_spent;
		}

		return $user_sessions_attended;
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
			if($this->vitero_object->getLearningProgressModeMulti())
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

		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
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
}