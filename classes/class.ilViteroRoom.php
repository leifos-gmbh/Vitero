<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class
 *
 * @author Stefan Meyer <meyer@leifos.com>
 */
class ilViteroRoom
{
    const TYPE_CAFE = 1;
	const TYPE_STD = 2;

	const REC_ONCE = 1;
	const REC_DAILY = 2;
	const REC_WEEKLY = 3;
	const REC_WORKDAYS = 4;
	const REC_WEKKEND = 5;


	private $start = null;
	private $end = null;

	private $iscafe = false;
	private $buffer_before = 0;
	private $buffer_after = 0;

	private $capture = false;

	private $rep = self::REC_ONCE;
	private $rep_date = null;

	private $roomsize = 20;

	/**
	 * @var ilViteroPhone
	 */
	private $phone = null;

	public function setStart($a_start)
	{
		$this->start = $a_start;
	}

	public function getStart()
	{
		return $this->start;
	}

	public function setEnd($a_end)
	{
		$this->end = $a_end;
	}

	public function getEnd()
	{
		return $this->end;
	}

	public function enableCafe($a_stat)
	{
		$this->iscafe = $a_stat;
	}

	public function isCafe()
	{
		return $this->iscafe;
	}

	public function setBufferBefore($a_before)
	{
		$this->buffer_before = $a_before;
	}

	public function getBufferBefore()
	{
		return $this->buffer_before;
	}

	public function setBufferAfter($a_after)
	{
		$this->buffer_after = $a_after;
	}

	public function getBufferAfter()
	{
		return $this->buffer_after;
	}

	public function getRoomSize()
	{
		return $this->roomsize;
	}

	public function setRoomSize($a_rooms)
	{
		$this->roomsize = $a_rooms;
	}

	public function getRepetition()
	{
		return $this->rep;
	}

	public function getRepetitionString()
	{
		switch($this->rep)
		{
			case ilViteroUtils::REC_ONCE:
				return 'once';

			case ilViteroUtils::REC_DAILY:
				return 'daily';

			case ilViteroUtils::REC_WEEKLY:
				return 'weekly';

			case ilViteroUtils::REC_WEEKDAYS:
				return 'workdays';

			case ilViteroUtils::REC_WEEKENDS:
				return 'weekends';

			default:
				return 'once';
		}
	}

	public function setRepetition($a_rep)
	{
		$this->rep = $a_rep;
	}

	public function setRepetitionEndDate($date)
	{
		$this->rep_date = $date;
	}

	public function getRepetitionEndDate()
	{
		return $this->rep_date;
	}

	public function setBookingId($a_id)
	{
		$this->bookingid = $a_id;
	}

	/**
	 * @param ilViteroPhone $phone
	 */
	public function setPhone(ilViteroPhone $phone) {
		$this->phone = $phone;
	}

	/**
	 * @return ilViteroPhone
	 */
	public function getPhone() {
		return $this->phone;
	}

	/**
	 * @param bool $a_status
	 */
	public function enableRecorder($a_status) {
		$this->capture = $a_status;
	}

	/**
	 * @return bool
	 */
	public function isRecorderEnabled() {
		return $this->capture;
	}

}
?>