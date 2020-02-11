<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author Jesús López <lopez@leifos.com>
 */
class ilViteroStatisticSoapConnector extends ilViteroSoapConnector
{
	const WSDL_NAME = 'statistic.wsdl';

	public function getSessionRecordingById($a_session_recording_id, $a_timezone = "")
	{
		try {
			$this->initClient();

			$session = new stdClass();
			$session->sessionrecordingid = $a_session_recording_id;
			$session_recording = $this->getClient()->getSessionRecordingById($session);

			return $session_recording;

		}
		catch (Exception $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Get session recording by id failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	/**
	 * Gets information about session recordings in a specific time slot.
	 * @param $a_time_slot_start
	 * @param $a_time_slot_end
	 * @throws ilViteroConnectorException
	 */
	public function getSessionRecordingsByTimeSlot($a_time_slot_start, $a_time_slot_end)
	{
		try {
			$this->initClient();

			$request = new stdClass();
			$request->timeslotstart = $a_time_slot_start;
			$request->timeslotend = $a_time_slot_end;
			$session_recording = $this->getClient()->getSessionRecordingsByTimeSlot($request);

			return $session_recording;
		}
		catch(Exception $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Get session recordings by timeslot failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}

	}

	/**
	 * @param $a_time_slot_start
	 * @param $a_time_slot_end
	 * @param int $a_customer_id
	 * @param int $a_vgroup_id
	 * @return mixed
	 * @throws \ilViteroConnectorException
	 */
	public function getSessionAndUserRecordingsByTimeSlot($a_time_slot_start, $a_time_slot_end, $a_customer_id = 0, $a_vgroup_id= 0)
	{
		try {
			$this->initClient();

			$request = new stdClass();

			$request->timeslotstart = $a_time_slot_start;
			$request->timeslotend = $a_time_slot_end;
			$request->customerid = $a_customer_id;
			$request->groupid = $a_vgroup_id;

			$recording_data = $this->getClient()->getSessionAndUserRecordingsByTimeSlot($request);

			return $recording_data;
		}
		catch(Exception $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Get session recordings by timeslot failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	/**
	 * @return string
	 */
	protected function getWsdlName()
	{
		return self::WSDL_NAME;
	}


	public function getUserRecordingById()
	{
		//Not necessary to implement so far.
	}

	public function getSessionAndUserRecordingsBySessionId()
	{
		//Not necessary to implement so far.
	}

	public function getCapacityRecordingByDate()
	{
		//Not necessary to implement so far.
	}

	public function getCapacityRecordingsByTimeSlot()
	{
		//Not necessary to implement so far.
	}

}
?>
