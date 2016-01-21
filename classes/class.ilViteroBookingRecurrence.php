<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Calendar/interfaces/interface.ilCalendarRecurrenceCalculation.php';
include_once './Services/Calendar/classes/class.ilCalendarRecurrence.php';

/**
 * Description of class
 *
 * @author Stefan Meyer <meyer@leifos.com>
 */
class ilViteroBookingRecurrence implements ilCalendarRecurrenceCalculation
{

	private $frequence_type = '';
	private $byday = array();
	private $frequence_until_date = null;

	private $booking_id;


	/**
	 * Constructor
	 * @param object $booking
	 */
	public function __construct($booking)
	{
		$this->booking_id = $booking->bookingid;

		switch($booking->repetitionpattern)
		{
			case 1:
				$this->frequence_type = ilCalendarRecurrence::FREQ_DAILY;
				break;

			case 2:
				$this->frequence_type = ilCalendarRecurrence::FREQ_WEEKLY;
				break;

			case 3:
				$this->frequence_type = ilCalendarRecurrence::FREQ_WEEKLY;
				$this->byday = array('MO','TU','WE','TH','FR');
				break;

			case 4:
				$this->frequence_type = ilCalendarRecurrence::FREQ_WEEKLY;
				$this->byday = array('SA','SU');
				break;

			default:
		}

		if($booking->repetitionenddate)
		{
			$this->frequence_until_date = ilViteroUtils::parseSoapDate($booking->repetitionenddate);
		}
	}



	/**
	 * Get Frequence type of recurrence
	 */
	public function getFrequenceType()
	{
		return $this->frequence_type;
	}

	public function setFrequenceType($a_type)
	{
		$this->frequence_type = $a_type;
	}

	/**
	 * Get timezone of recurrence
	 */
	public function getTimeZone()
	{
		ilTimeZone::_getDefaultTimeZone();
	}

	/**
	 * Get number of recurrences
	 */
	public function getFrequenceUntilCount()
	{
		return 0;
	}


	/**
	 * Get end data of recurrence
	 */
	public function getFrequenceUntilDate()
	{
		if($this->frequence_until_date instanceof ilDateTime)
		{
			return $this->frequence_until_date;
		}
		return $this->frequence_until_date;
	}

	public function setFrequenceUntilDate(ilDate $dt)
	{
		$this->frequence_until_date = $dt;
	}

	/**
	 * Get interval of recurrence
	 */
	public function getInterval()
	{
		return 1;
	}

	/**
	 * Get BYMONTHList
	 */
	public function getBYMONTHList()
	{
		return array();
	}

	/**
	 * Get BYWEEKNOList
	 */
	public function getBYWEEKNOList()
	{
		return array();
	}

	/**
	 * Get BYYEARDAYLIST
	 */
	public function getBYYEARDAYList()
	{
		return array();
	}

	/**
	 * GEt BYMONTHDAY List
	 */
	public function getBYMONTHDAYList()
	{
		return array();
	}


	/**
	 * Get BYDAY List
	 */
	public function getBYDAYList()
	{
		return $this->byday;
	}

	/**
	 * Get BYSETPOS List
	 */
	public function getBYSETPOSList()
	{
		return array();
	}

	/**
	 * Get exclusion dates
	 */
	public function getExclusionDates()
	{
		return ilViteroBookingReccurrenceExclusion::getExclusionDates($this->booking_id);
	}


	/**
	 * validate recurrence
	 */
	public function validate()
	{
		if(!$this->frequence_type)
		{
			return false;
		}
		return true;
	}

}
?>
