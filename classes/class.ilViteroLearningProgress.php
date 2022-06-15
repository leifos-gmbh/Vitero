<?php

/**
 * Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE
 * @author Jesús López Reyes <lopez@leifos.com>
 */
class ilViteroLearningProgress
{
    const PASSED = "passed";
    const NOT_PASSED = "not passed";
    const CRON_PLUGIN_ID = "xvitc";
    /**
     * @var null | \ilLogger
     */
    private $logger = null;
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
        global $DIC;

        $this->logger = $DIC->logger()->xvit();

        $this->vitero_object = new ilObjVitero();
        $this->user_mapping  = new ilViteroUserMapping();
    }

    /**
     * Booking in vitero = Appointment in ILIAS
     * @param int vitero group id $a_vitero_group_id
     * @throws ilDateTimeException
     */
    public function updateLearningProgress(int $a_vgroup_id = 0)
    {
        $statistic_connector = new ilViteroStatisticSoapConnector();
        $booking_connector   = new ilViteroBookingSoapConnector();

        $settings    = new ilViteroSettings();
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
        $this->logger->dump($session_and_user_recordings, ilLogLevel::DEBUG);

        if (is_object($session_and_user_recordings->sessionrecording)) {
            $session_and_user_recordings = array($session_and_user_recordings->sessionrecording);
        } else {
            if (is_array($session_and_user_recordings->sessionrecording)) {
                $session_and_user_recordings = $session_and_user_recordings->sessionrecording;
            }
        }

        // Notice for V11: The booking id of the session is now the real booking id, and can be used as such.
        // Notice for V10 and below: The booking id here is not a real booking id because vitero needs this to keep backward compatibility.
        // Notice for V10 and below: The booking id is a bookingTimeId and we can get a booking obj. via getBookingTimeId(bookingTimeId)
        foreach ($session_and_user_recordings as $session_user_recording) {
            $ilias_object_id = ilObjVitero::lookupObjIdByGroupId($session_user_recording->groupid);

            //Omit this group id if there is not an ILIAS vitero session assigned.
            if ($ilias_object_id == 0) {
                continue;
            }

            $this->vitero_object->setId($ilias_object_id);

            $this->vitero_object->readLearningProgressSettings();

            if ($this->vitero_object->isLearningProgressActive()) {

                try {
                    if ($settings->getViteroVersion() < ilViteroSettings::VITERO_VERSION_ELEVEN) {
                        $booking = $booking_connector->getBookingByBookingTimeId($session_user_recording->bookingid);
                    } else {
                        $booking = $booking_connector->getBookingById($session_user_recording->bookingid);
                    }
                } catch (ilViteroConnectorException $e) {
                    $this->logger->warning('Skipped recording with error: ' . $e->getViteroMessage());
                    continue;
                }

                //parse vitero string dates to ilDateTime
                $booking_start = ilViteroUtils::parseSoapDate($booking->booking->start)->getUnixTime();
                $booking_end = ilViteroUtils::parseSoapDate($booking->booking->end)->getUnixTime();

                $booking_duration_seconds = $booking_end - $booking_start;

                $buffer_start = $booking->startbuffer;
                $buffer_end   = $booking->endbuffer;

                //for recurring appointments, find the start time of the appointment this session falls into
                $session_start = ilViteroUtils::parseSoapDate($session_user_recording->sessionstart);
                $session_end = ilViteroUtils::parseSoapDate($session_user_recording->sessionend);

                $search_start = clone $session_start;
                $search_start->increment(ilDateTime::YEAR, -5);
                $search_end = clone $session_end;
                $search_end->increment(ilDateTime::YEAR, 1);

                $app_list = ilViteroUtils::calculateBookingAppointments($search_start, $search_end, $booking->booking);
                foreach ($app_list as $app) {

                    $app_start = clone $app;
                    $app_start->increment(ilDateTime::MINUTE, $buffer_start * -1);

                    $app_end = clone $app;
                    $app_end->setDate($app->get(IL_CAL_UNIX) + $booking_duration_seconds, IL_CAL_UNIX);
                    $app_end->increment(ilDateTime::MINUTE, $buffer_end);

                    if (ilDateTime::_within($session_start, $app_start, $app_end)) {
                        $booking_start = $app->getUnixTime();
                        $booking_end = $booking_start + $booking_duration_seconds;
                    }
                }

                $userrecordings = array();

                if (is_object($session_user_recording->userrecording)) {
                    $userrecordings = array($session_user_recording->userrecording);
                } else {
                    if (is_array($session_user_recording->userrecording)) {
                        $userrecordings = $session_user_recording->userrecording;
                    }
                }

                $user_attendances = array();

                foreach ($userrecordings as $userrecording) {
                    $session_recording_id = $userrecording->sessionrecordingid;

                    if ($userrecording->userend >= $booking->booking->start) {
                        $user_percent_attended = 0;

                        $this->logger->debug('Booking duration: ' . $booking_duration_seconds);

                        $user_start = ilViteroUtils::parseSoapDate($userrecording->userstart)->getUnixTime();
                        $user_end = ilViteroUtils::parseSoapDate($userrecording->userend)->getUnixTime();

                        //get the effective start and end
                        $real_start = max($booking_start, $user_start);
                        $real_end = min($booking_end, $user_end);

                        //get the effective time spent by the user in the booking session
                        $user_time_attended = max($real_end - $real_start, 0);

                        $this->logger->debug('Spent time is: ' . $user_time_attended);

                        //get percentage of the effective time spent rounded always down only if user has effective time.
                        if ($user_time_attended > 0) {
                            $user_percent_attended = floor($user_time_attended * 100 / $booking_duration_seconds);
                        }

                        $this->logger->debug('Percent attended: ' . $user_percent_attended);

                        $user_id = $this->user_mapping->getIUserId($userrecording->userid);

                        //if user mapped properly
                        if ($user_id) {
                            $user_attendances[$user_id]['session_recording_id'] = $session_recording_id;
                            isset($user_attendances[$user_id]['user_percent_attended']) ?
                                $user_attendances[$user_id]['user_percent_attended'] += $user_percent_attended :
                                $user_attendances[$user_id]['user_percent_attended'] = $user_percent_attended;
                        }
                    }
                }

                $this->logger->dump($user_attendances, ilLogLevel::DEBUG);
                foreach ($user_attendances as $user_id => $user_attendance) {
                    $this->updateUserRecordingAttendance(
                        $ilias_object_id,
                        $user_id,
                        $user_attendance['session_recording_id'],
                        $user_attendance['user_percent_attended'],
                        $booking_start
                    );
                }

            }

        }
        ilLPStatusWrapper::_refreshStatus($ilias_object_id);
        ilViteroUtils::updateLastSyncDate();

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
        if ($last_cron_ejecution_date > 0) {
            $start_range = new ilDateTime($last_cron_ejecution_date, IL_CAL_UNIX);
            $start_range->increment(IL_CAL_DAY, -1);
        } else {
            $start_range = new ilDateTime(time(), IL_CAL_UNIX);
            $start_range->increment(IL_CAL_YEAR, -5);
        }

        $start_unix = $start_range->getUnixTime();
        $start_str  = date('YmtHi', $start_unix);

        $end_range = new ilDateTime(time(), IL_CAL_UNIX);
        $end_range->increment(IL_CAL_YEAR, 1);
        $end_unix = $end_range->getUnixTime();
        $end_str  = date('YmtHi', $end_unix);

        return array(
            "start" => $start_str,
            "end"   => $end_str
        );
    }

    /**
     * @param $a_recording_id
     * @param $a_recording_user_id
     * @param $user_percent_attended
     */
    public function updateUserRecordingAttendance($a_ilias_object_id, $a_user_id, $a_sessionrecording_id, $a_user_percent_attended, $a_booking_start)
    {
        if ($this->isNotUserRecordingStoredInDB($a_user_id, $a_sessionrecording_id)) {
            $this->insertUserRecording($a_ilias_object_id, $a_sessionrecording_id, $a_user_id, $a_user_percent_attended, $a_booking_start);
        }

    }

    /**
     * @param $a_recording_id
     * @return bool
     * @throws ilDatabaseException
     */
    protected function isNotUserRecordingStoredInDB($a_user_id, $a_recording_id)
    {
        global $DIC;

        $db = $DIC->database();

        $query = 'SELECT user_id FROM rep_robj_xvit_recs' .
            ' WHERE recording_id = ' . $db->quote($a_recording_id, 'integer') .
            ' AND user_id = ' . $db->quote($a_user_id, 'integer');

        $res = $db->query($query);

        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            return false;
        }

        return true;
    }

    /**
     * @param $a_recording_id
     * @param $a_user_id
     * @param $a_user_percent_attended
     */
    protected function insertUserRecording($a_ilias_object_id, $a_userrecording_id, $a_user_id, $a_user_percent_attended, $a_booking_id)
    {
        global $DIC;

        $db = $DIC->database();

        $sql = 'INSERT INTO rep_robj_xvit_recs (user_id,obj_id,recording_id,percentage,app_start) ' .
            'VALUES(' .
            $db->quote($a_user_id, 'integer') . ', ' .
            $db->quote($a_ilias_object_id, 'integer') . ', ' .
            $db->quote($a_userrecording_id, 'integer') . ', ' .
            $db->quote($a_user_percent_attended, 'integer') . ', ' .
            $db->quote($a_booking_id, 'integer') .
            ')';

        $db->manipulate($sql);
    }

    /**
     * @param $a_completed_sessions
     * @return array
     */
    public function getUsersStatus($a_completed_sessions)
    {
        $users_status = array();

        foreach ($a_completed_sessions as $user_id => $total_passed) {
            $status = self::NOT_PASSED;
            if ($this->vitero_object->isLearningProgressModeMultiActive()) {
                if ($total_passed >= $this->vitero_object->getLearningProgressMinSessions()) {
                    $status = self::PASSED;
                }
            } else {
                if ($total_passed > 0) {
                    $status = self::PASSED;
                }
            }

            $users_status[] = array(
                "user_id" => $user_id,
                "obj_id"  => $this->vitero_object->getId(),
                "status"  => $status
            );
        }

        return $users_status;
    }
}