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
	 * TODO pending real sessions in vitero platform to test this.
	 * @throws ilDateTimeException
	 */
	public function updateLearningProgress()
	{
		$statistic_connector = new ilViteroStatisticSoapConnector();

		$settings = new ilViteroSettings();
		$customer_id = $settings->getCustomer();

		//TODO define properly these slots. Store them if necessary (current end time will be next start)

		$start = new ilDateTime(time(),IL_CAL_UNIX);//2019-06-24 17:04:57

		$end = clone $start;

		$start->increment(IL_CAL_YEAR,-5);
		$start = $start->getUnixTime();
		$start = date('YmtHi',$start);
		$end->increment(IL_CAL_YEAR,1);
		$end = $end->getUnixTime();
		$end = date('YmtHi',$end);

		//TODO uncomment this and remove dummy array.
		//$session_and_user_recordings = $statistic_connector->getSessionAndUserRecordingsByTimeSlot($start, $end, $customer_id);

		//TODO parse the recordings
		//$session_and_user_recordings = $this->parseRecordings($session_and_user_recordings);
		$session_and_user_recordings = $this->dummyArray();

		// READ ONLY SESSIONS WITH FUTURE APPOINTMENTS.
		$usersAndStatusToUpdate = $this->getUsersAndStatusToUpdate($session_and_user_recordings);
		ilLoggerFactory::getRootLogger()->dump($usersAndStatusToUpdate);
		foreach ($usersAndStatusToUpdate as $status_data)
		{
			//TODO-> This is the current process ending.
			$this->updateUserRecordingAttendance($status_data['userrecordingid'], $status_data['userid'], $status_data['percentage']);
		}
		ilLoggerFactory::getRootLogger()->dump($usersAndStatusToUpdate);
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

	public function dummyArray()
	{
		$array_user_recording = array();

		$array_user_recording[] = array(
			"userstart" => "201906251545",
			"userend" => "201906251615",
			"userid" => 3,
			"sessionrecordingid" => 1,
			"userrecordingid" => 1
		);

		/*$array_user_recording[] = array(
			"userstart" => "201906251640",
			"userend" => "201906251645",
			"userid" => 3,
			"sessionrecordingid" => 1,
			"userrecordingid" => 2
		);*/

		/*$array_user_recording[] = array(
			"userstart" => "201906251650",
			"userend" => "201906251715",
			"userid" => 3,
			"sessionrecordingid" => 1,
			"userrecordingid" => 3
		);*/

		$array_user_recording[] = array(
			"userstart" => "201906251700",
			"userend" => "201906251715",
			"userid" => 3,
			"sessionrecordingid" => 2,
			"userrecordingid" => 4
		);

		$array_user_recording[] = array(
			"userstart" => "201906251605",
			"userend" => "201906251715",
			"userid" => 4,
			"sessionrecordingid" => 1,
			"userrecordingid" => 5
		);

		$session_and_user_recordings = array();
		$session_and_user_recordings[] = array(
			"groupid" => 4,
			"sessionstart" => "201906251600",
			"sessionend" => "201906251700",
			"userrecording" => $array_user_recording
		);

		return $session_and_user_recordings;
	}

	/**
	 * get the moment when the session started for the user
	 * @param $a_session_start
	 * @param $a_user_start
	 * @return mixed
	 */
	public function getRealStartDateTime($a_session_start, $a_user_start)
	{
		$real_start = $a_session_start;

		if($a_user_start > $a_session_start){
			$real_start = $a_user_start;
		}

		return $real_start;
	}

	/**
	 * Get the moment when the session finished for the user
	 * @param $a_session_end
	 * @param $a_user_end
	 * @return mixed
	 */
	public function getRealEndDateTime($a_session_end, $a_user_end)
	{
		$real_end = $a_session_end;

		if($a_user_end < $a_session_end) {
			$real_end = $a_user_end;
		}

		return $real_end;
	}

	/**
	 * @param $a_recordings
	 * @return array
	 */
	public function getUsersAndStatusToUpdate($a_recordings)
	{
		$sessions_user_data = array();

		foreach($a_recordings as $recording)
		{
			$ilias_object_id = ilObjVitero::lookupObjIdByGroupId($recording['groupid']);

			$this->vitero_object->setId($ilias_object_id);

			$this->vitero_object->readLearningProgressSettings();

			if($this->vitero_object->getLearningProgress())
			{
				$session_start  = strtotime($recording['sessionstart']);
				$session_end    = strtotime($recording['sessionend']);

				$sessions_user_data = $this->getUserSessionsAttended($recording['userrecording'], $session_start, $session_end);
			}
		}

		return $sessions_user_data;
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

		foreach($a_user_recording as $record)
		{
			//TODO: USER RECORDINGS ALWAYS HAVE USER START AND USER END  datetime. This if maybe is not needed.
			//We only calculate the Learning Progress if the user loged out from vitero session.
			if($record['userend'] > 0)
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
	 * @param $a_user_sessions_attended
	 * @param $a_session_duration
	 * @param $a_lp_min_percent
	 * @return array
	 */
	public function getCompletedSessions($a_user_sessions_attended, $a_session_duration, $a_lp_min_percent)
	{
		$completed_sessions = array();

		foreach($a_user_sessions_attended as $user_id => $attendance)
		{
			$attended = 0;
			foreach($attendance as $session => $seconds)
			{
				$user_percent_attended = floor($seconds * 100 / $a_session_duration);

				if($user_percent_attended >= $a_lp_min_percent)
				{
					$attended++;
				}
			}
			$completed_sessions[$user_id] = $attended;
		}

		return $completed_sessions;
	}

	/**
	 * @param $a_recording_id
	 * @param $a_recording_user_id
	 * @param $user_percent_attended
	 */
	public function updateUserRecordingAttendance($a_userrecording_id, $a_recording_user_id, $a_user_percent_attended)
	{
		if($this->isNotUserRecordingStoredInDB($a_userrecording_id))
		{
			$this->insertUserRecording($a_userrecording_id, $a_recording_user_id, $a_user_percent_attended);
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
	protected function insertUserRecording($a_userrecording_id, $a_user_id, $a_user_percent_attended)
	{
		global $DIC;

		$db = $DIC->database();

		$sql = 'INSERT INTO rep_robj_xvit_recs (user_id,obj_id,recording_id,percentage) ' .
			'VALUES(' .
			$db->quote($a_user_id, 'integer') . ', ' .
			$db->quote($this->vitero_object->getId(), 'integer') . ', ' .
			$db->quote($a_userrecording_id, 'integer') . ', ' .
			$db->quote($a_user_percent_attended,'integer') .
			')';

		ilLoggerFactory::getRootLogger()->debug("INSERT = ".$sql);

		$db->manipulate($sql);
	}
}