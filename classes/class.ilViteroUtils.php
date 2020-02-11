<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Vitero utility functions
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id$
 */
class ilViteroUtils
{
	const REC_ONCE = 0;
	const REC_DAILY = 1;
	const REC_WEEKLY = 2;
	const REC_WEEKDAYS = 3;
	const REC_WEEKENDS = 4;


	/**
	 * Parse date string from soap
	 * @param string $a_datetime
	 * @return ilDateTime 
	 */
	public static function parseSoapDate($a_datetime)
	{
		$a_datetime .= '00';

		if(strlen($a_datetime) != 14)
		{
			return new ilDateTime(0,IL_CAL_UNIX,  ilViteroSoapConnector::WS_TIMEZONE);
		}

		$date = array(
			'hours'		=> substr($a_datetime,8,2),
			'minutes'	=> substr($a_datetime,10,2),
			'seconds'	=> 0,
			'mon'		=> substr($a_datetime,4,2),
			'mday'		=> substr($a_datetime,6,2),
			'year'		=> substr($a_datetime,0,4)
		);

		if(substr($a_datetime,8) == '0000')
		{
			// Map to date
			return new ilDate($date,IL_CAL_FKT_GETDATE);
		}
		else
		{
			return new ilDateTime($date,IL_CAL_FKT_GETDATE,  ilViteroSoapConnector::WS_TIMEZONE);
		}
	}

	public static function randPassword($length = 8)
	{
	    mt_srand((double)microtime()*1000000);
	    $charset = "123456789ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$length  = strlen($charset)-1;
	    $code    = '';
		for($i = 0;$i < $length; $i++)
		{
			$code .= $charset{mt_rand(0, $length)};
		}
		return $code;
	}

	/**
	 * Calculate booking appointments
	 * Returns an array with booking appointments
	 *
	 * @param ilDate $start
	 * @param ilDate $end
	 * @param object $booking
	 * @return array
	 */
	public static function calculateBookingAppointments(ilDateTime $start, ilDateTime $end, $booking)
	{
		include_once './Services/Calendar/classes/class.ilCalendarRecurrenceCalculator.php';
		$rcalc = new ilCalendarRecurrenceCalculator(
			new ilViteroBookingDatePeriod($booking),
			new ilViteroBookingRecurrence($booking)
		);
		return $rcalc->calculateDateList($start, $end);
	}

	/**
	 * Lookup next booking
	 * @param ilDate $start
	 * @param ilDate $end
	 * @param <type> $group_id
	 * @return array
	 */
	public static function lookupNextBooking(ilDateTime $start, ilDateTime $end, $group_id)
	{

		try {
			$con = new ilViteroBookingSoapConnector();
			$bookings = $con->getByGroupAndDate($group_id, $start, $end);
		}
		catch(Exception $e) {
			$GLOBALS['ilLog']->write(__METHOD__.': Vitero connection failed with message: '. $e->getMessage());
			return NULL;
		}

		$bookings_arr = array();
		if(is_object($bookings->booking))
		{
			$bookings_arr = array($bookings->booking);
		}
		elseif(is_array($bookings->booking))
		{
			$bookings_arr = $bookings->booking;
		}
		
		include_once './Services/Calendar/classes/class.ilDateList.php';
		$next_booking['start'] = NULL;
		foreach($bookings_arr as $booking)
		{
			// Calculate duration
			$fstart = ilViteroUtils::parseSoapDate($booking->start);
			$fend = ilViteroUtils::parseSoapDate($booking->end);
			$duration = $fend->get(IL_CAL_UNIX) - $fstart->get(IL_CAL_UNIX);

			$buffer_start = $booking->startbuffer;
			$buffer_end = $booking->endbuffer;

			$apps = self::calculateBookingAppointments($start, $end, $booking);

			foreach($apps as $app)
			{
				if($next_booking['start'] instanceof ilDateTime)
				{
					if(ilDateTime::_before($app, $next_booking['start']))
					{
						$next_booking['start'] = $app;

						$next_booking['open'] = clone $next_booking['start'];
						$next_booking['open']->increment(ilDateTime::MINUTE, $buffer_start * -1);

						$next_booking['end'] = clone $next_booking['start'];
						$next_booking['end']->setDate($next_booking['end']->get(IL_CAL_UNIX) + $duration,IL_CAL_UNIX);

						$next_booking['closed'] = clone $next_booking['end'];
						$next_booking['closed']->increment(ilDateTime::MINUTE, $buffer_end);

						$next_booking['id'] = $booking->bookingid;
					}
				}
				else
				{
					$next_booking['start'] = $app;

					$next_booking['open'] = clone $next_booking['start'];
					$next_booking['open']->increment(ilDateTime::MINUTE, $buffer_start * -1);

					$next_booking['end'] = clone $next_booking['start'];
					$next_booking['end']->setDate($next_booking['end']->get(IL_CAL_UNIX) + $duration,IL_CAL_UNIX);

					$next_booking['closed'] = clone $next_booking['end'];
					$next_booking['closed']->increment(ilDateTime::MINUTE, $buffer_end);

					$next_booking['id'] = $booking->bookingid;

				}
			}
		}
		return $next_booking;
	}

	public static function getOpenRoomBooking($a_group_id)
	{
		$now = new ilDateTime(time(),IL_CAL_UNIX);

		$earlier = clone $now;

		$later = clone $now;
		$later->increment(IL_CAL_DAY, 5);
		$booking = self::lookupNextBooking($earlier, $later, $a_group_id);
		
		if(!$booking['open'] instanceof  ilDateTime)
		{
			return 0;
		}
		if(ilDateTime::_before($booking['open'], $now) and ilDateTime::_after($booking['closed'], $now))
		{
			return $booking['id'];
		}
		return 0;
	}
	
	/**
	 * Returns the next booking independent from the "open status".
	 */
	public static function getNextBooking($a_group_id)
	{
		$now = new ilDateTime(time(), IL_CAL_UNIX);
		$earlier = clone $now;
		
		$later = clone $now;
		$later->increment(IL_CAL_DAY, 365);
		
		$booking = self::lookupNextBooking($earlier, $later, $a_group_id);
		
		return isset($booking['id']) ? $booking['id'] : 0;
	}

	public static function recurrenceToString($a_rec)
	{
		switch($a_rec)
		{
			case self::REC_ONCE:
				return ilViteroPlugin::getInstance()->txt('rec_once');

			case self::REC_DAILY:
				return ilViteroPlugin::getInstance()->txt('rec_daily');

			case self::REC_WEEKLY:
				return ilViteroPlugin::getInstance()->txt('rec_weekly');

			case self::REC_WEEKDAYS:
				return ilViteroPlugin::getInstance()->txt('rec_weekdays');

			case self::REC_WEEKENDS:
				return ilViteroPlugin::getInstance()->txt('rec_weekends');
		}
	}

	/**
	 * Get available room sizes
	 *
	 * @return array
	 */
	public static function getRoomSizeList()
	{
		try {
			$licence_service = new ilViteroLicenceSoapConnector();
			$modules = $licence_service->getModulesForCustomer(ilViteroSettings::getInstance()->getCustomer());
		}
		catch(ilViteroConnectorException $e)
		{
			return array();
		}

		$available_rooms = array();
		foreach((array) $modules->modules->module as $key => $mod)
		{
			if($mod->type != 'ROOM')
			{
				continue;
			}
			$available_rooms[(int) $mod->roomsize] = (int) $mod->roomsize .' '. ilViteroPlugin::getInstance()->txt('participants');
		}
		return $available_rooms;
	}

	/**
	 * @return bool
	 */
	public static function hasCustomerMonitoringMode()
	{
		global $DIC;

		$logger = $DIC->logger()->xvit();

		try {
			$licence_connector = new ilViteroLicenceSoapConnector();
			$modules = $licence_connector->getModulesForCustomer(ilViteroSettings::getInstance()->getCustomer());

			$logger->dump($modules, \ilLogLevel::DEBUG);
			foreach($modules->modules->module as $module)
			{
				if($module->type == "MONITORING") {
					return true;
				}
			}
		}
		catch(\ilViteroConnectorException $e) {
			$logger->warning('Reading active modules failed with message: ' . $e->getMessage());
			return false;
		}
		return false;
	}

	/**
	 * Get the date of the last vitero synchronization.
	 * @return int
	 * @throws ilDatabaseException
	 */
	public static function getLastSyncDate()
	{
		global $DIC;

		$db = $DIC->database();

		$query = 'SELECT last_sync FROM rep_robj_xvit_date';

		$res = $db->query($query);

		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			return $row->last_sync;
		}

		return 0;
	}

	/**
	 * Insert/Update the last vitero synchronization.
	 * @return int|void
	 * @throws ilDatabaseException
	 */
	public static function updateLastSyncDate()
	{
		global $DIC;

		$db = $DIC->database();

		if(self::getLastSyncDate() > 0)
		{
			$query = "UPDATE rep_robj_xvit_date SET last_sync = " . $db->quote(time(),"integer");

			return $db->manipulate($query);
		}

		$query = "INSERT INTO rep_robj_xvit_date (last_sync) VALUES(" . $db->quote(time(), "integer") . ")";

		return $db->manipulate($query);
	}
}
?>
