<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * 
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroBookingSoapConnector.php 33166 2012-02-14 13:49:39Z smeyer $
 */
class ilViteroBookingSoapConnector extends ilViteroSoapConnector
{
	const WSDL_NAME = 'booking.wsdl';


	/**
	 * Create Group
	 * @param ilViteroGroupSoap $group
	 * @throws ilViteroConnectorException $e
	 */
	public function create(ilViteroRoom $room, $a_group_id)
	{
		try {
			
			$this->initClient();

			// Wrap into single group object
			$booking = new stdClass();
			$booking->startbuffer = $room->getBufferBefore();
			$booking->endbuffer = $room->getBufferAfter();
			$booking->groupid = $a_group_id;
			$booking->roomsize = $room->getRoomSize();

			$booking->phone = $room->getPhone();
			$booking->capture = $room->isRecorderEnabled();

			$booking->ignorefaults = false;
			$booking->cafe = $room->isCafe();

			if($room->isCafe())
			{
				$GLOBALS['ilLog']->write(__METHOD__.': Creating new cafe');
				$booking->start = $room->getStart()->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
				$booking->end = $room->getEnd()->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
				$booking->repetitionpattern = 'daily';
				$booking->repetitionenddate = $room->getRepetitionEndDate()->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
			}
			else
			{
				$GLOBALS['ilLog']->write(__METHOD__.': Creating new standard room');
				$booking->start = $room->getStart()->get(IL_CAL_FKT_DATE,'YmdHi', self::CONVERT_TIMZONE);
				$booking->end = $room->getEnd()->get(IL_CAL_FKT_DATE,'YmdHi', self::CONVERT_TIMZONE);
				$booking->repetitionpattern = $room->getRepetitionString();
				if($room->getRepetitionEndDate() instanceof ilDateTime)
				{
					$booking->repetitionenddate = $room->getRepetitionEndDate()->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
				}
			}

			$booking->timezone = self::WS_TIMEZONE;

			$booking->phone = $room->getPhone();

			$container = new stdClass();
			$container->booking = $booking;

			$response = $this->getClient()->createBooking($container);

			$GLOBALS['ilLog']->write(__METHOD__.print_r($this->getClient()->__getLastRequest(),true));
			$GLOBALS['ilLog']->write(__METHOD__.print_r($this->getClient()->__getLastResponse(),true));
			
			return $response->bookingid;
		}
		catch(SoapFault $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Creating vitero group failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	/**
	 * Update booking
	 * @param ilViteroRoom $room
	 * @param $a_group_id
	 * @throws ilViteroConnectorException
	 */
	public function updateBooking(ilViteroRoom $room, $a_group_id)
	{
		try {
			$this->initClient();

			// Wrap into single group object
			$booking = new stdClass();
			$booking->bookingid = $room->bookingid;

			if($room->isCafe())
			{
				$booking->start = $room->getStart()->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
				$booking->end = $room->getEnd()->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);

			}
			else
			{
				$booking->start = $room->getStart()->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMEZONE_FIX);
				$booking->end = $room->getEnd()->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMEZONE_FIX);
			}

			$booking->startbuffer = $room->getBufferBefore();
			$booking->endbuffer = $room->getBufferAfter();

			$this->getClient()->updateBooking($booking);
			$this->getLogger()->dump($booking,ilLogLevel::DEBUG);
			$this->getLogger()->dump($this->getClient()->__getLastRequest(), ilLogLevel::DEBUG);
			$this->getLogger()->dump($this->getClient()->__getLastResponse(), ilLogLevel::DEBUG);

		}
		catch(SoapFault $e)
		{
			$code = $this->parseErrorCode($e);
			$this->getLogger()->logStack();
			$this->getLogger()->warning('Update vitero booking failed with message code: '.$code);
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}

		
	}

	public function getByGroupAndDate($a_groupid, ilDateTime $start, ilDateTime $end)
	{
		try {

			$this->initClient();

			// Wrap into single group object
			$req = new stdClass();
			$req->groupid = $a_groupid;
			$req->start = $start->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
			$req->end = $end->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
			$req->timezone = self::WS_TIMEZONE;
			$ret = $this->getClient()->getBookingListByGroupAndDate($req);
			return $ret;
		}
		catch(SoapFault $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Get booking list failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}

	}

	public function getBookingListByDate($a_customer_id, ilDateTime $start, ilDateTime $end)
	{
		try {

			$this->initClient();

			// Wrap into single group object
			$req = new stdClass();
			$req->customerid = $a_customer_id;
			$req->start = $start->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
			$req->end = $end->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
			$req->timezone = self::WS_TIMEZONE;

			$ret = $this->getClient()->getBookingListByDate($req);

			return $ret;
		}
		catch(Exception $e) {
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Get booking list failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	/**
	 * Copy bookings from one group to another
	 * @param <type> $a_old_group
	 * @param <type> $a_new_group
	 * @throws ilViteroConnectorException
	 */
	public function copyBookings($a_old_group, $a_new_group)
	{
		// Read all bookings of old group
		$now = new ilDateTime(time(),IL_CAL_UNIX);
		$later = clone $now;
		$later->increment(IL_CAL_YEAR,5);

		try
		{
			$bookings = $this->getByGroupAndDate($a_old_group, $now, $later);
		}
		catch(ilViteroConnectorException $e)
		{
			$GLOBALS['ilLog']->write(__METHOD__.': Copying vitero group failed with message '.$e);
			return false;
		}
		foreach((array) $bookings->booking as $booking)
		{
			$room = new ilViteroRoom();
			$room->setBufferBefore($booking->startbuffer);
			$room->setBufferAfter($booking->endbuffer);
			$room->setRoomSize($booking->roomsize);
			$room->enableCafe($booking->cafe ? true : false);


			$room->setStart(ilViteroUtils::parseSoapDate($booking->start));
			$room->setEnd(ilViteroUtils::parseSoapDate($booking->end));
			$room->setRepetition($booking->repetitionpattern);
			$rep_end = ilViteroUtils::parseSoapDate($booking->repetitionenddate);

			if($rep_end instanceof ilDateTime)
			{
				$room->setRepetitionEndDate($rep_end);
			}

			try {
				$this->create($room, $a_new_group);
			}
			catch(ilViteroConnectorException $e) {
				$GLOBALS['ilLog']->write(__METHOD__.': Copying vitero group failed with message '.$e);
			}
		}
	}

	public function getBookingById($a_id)
	{
		try {

			$this->initClient();

			// Wrap into single group object
			$req = new stdClass();
			$req->bookingid = $a_id;
			$req->timezone = self::WS_TIMEZONE;

			$ret = $this->getClient()->getBookingById($req);
			$this->getLogger()->dump($ret,ilLogLevel::DEBUG);
			$this->getLogger()->debug('Last request: ' . $this->getClient()->__getLastRequest());

			return $ret;
		}
		catch(SoapFault $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Get booking by id failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	public function deleteBooking($a_id)
	{
		try {

			$this->initClient();

			// Wrap into single group object
			$req = new stdClass();
			$req->bookingid = $a_id;
			$this->getClient()->deleteBooking($req);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			$GLOBALS['ilLog']->write(__METHOD__.': Last response: '.$this->getClient()->__getLastResponse());
			return true;
		}
		catch(SoapFault $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Delete booking by id failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}


	protected function getWsdlName()
	{
		return self::WSDL_NAME;
	}


}
?>