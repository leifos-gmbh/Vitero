<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

include_once('./Services/Calendar/classes/class.ilDateList.php');
include_once('./Services/Calendar/classes/class.ilTimeZone.php');
include_once('./Services/Calendar/classes/class.ilCalendarUtil.php');
include_once './Services/Calendar/classes/class.ilCalendarEntry.php';

/**
 * Stores exclusion dates for booking reccurences recurrences
 * @author  Stefan Meyer <meyer@leifos.com>
 * @version $Id: class.ilViteroBookingReccurrenceExclusion.php 32118 2011-12-14 16:18:33Z smeyer $
 */
class ilViteroBookingReccurrenceExclusion
{
    protected $exclusion = null;
    protected $book_id = 0;
    protected $exclusion_id = 0;

    protected $db = null;

    /**
     * Constructor
     * @return
     */
    public function __construct($a_exclusion_id = 0)
    {
        global $ilDB;

        $this->db           = $ilDB;
        $this->exclusion_id = $a_exclusion_id;

        if ($this->getId()) {
            $this->read();
        }
    }

    /**
     * Get exclusion id
     * @return
     */
    public function getId()
    {
        return $this->exclusion_id;
    }

    /**
     * Read exclusion
     * @return
     */
    protected function read()
    {
        global $ilDB;

        $query = "SELECT * FROM rep_robj_xvit_excl WHERE excl_id = " . $ilDB->quote($this->getId(), 'integer');
        $res   = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->book_id = $row->book_id;
            $this->setDate(new ilDate($row->excl_date, IL_CAL_DATE, 'UTC'));
        }
    }

    /**
     * Set exclusion date
     * @param ilDate $dt [optional]
     * @return
     */
    public function setDate(ilDate $dt = null)
    {
        $this->exclusion = $dt;
    }

    /**
     * Read exclusion dates
     * @param object $a_cal_id
     * @return
     */
    public static function getExclusionDates($a_book_id)
    {
        global $ilDB;

        $query = "SELECT excl_id FROM rep_robj_xvit_excl " .
            "WHERE book_id = " . $ilDB->quote($a_book_id, 'integer');

        $res        = $ilDB->query($query);
        $exclusions = array();
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $exclusions[] = new ilViteroBookingReccurrenceExclusion($row->excl_id);
        }
        return $exclusions;
    }

    /**
     * Set entry id (id of calendar appointment)
     * @param object $a_id
     * @return
     */
    public function setEntryId($a_id)
    {
        $this->book_id = $a_id;
    }

    /**
     * Save exclusion date to db
     * @return
     */
    public function save()
    {
        global $ilDB;

        if (!$this->getDate()) {
            return false;
        }

        $query = "INSERT INTO rep_robj_xvit_excl (excl_id,book_id,excl_date) " .
            "VALUES( " .
            $ilDB->quote($next_id = $ilDB->nextId('rep_robj_xvit_excl'), 'integer') . ', ' .
            $ilDB->quote($this->getEntryId(), 'integer') . ', ' .
            $ilDB->quote($this->getDate()->get(IL_CAL_DATE, '', 'UTC'), 'timestamp') .
            ')';
        $ilDB->manipulate($query);

        $this->exclusion_id = $next_id;
        return $this->getId();
    }

    /**
     * Get exclusion date
     * @return
     */
    public function getDate()
    {
        return $this->exclusion instanceof ilDate ? $this->exclusion : null;
    }

    /**
     * Get calendar entry id
     * @return
     */
    public function getEntryId()
    {
        return $this->book_id;
    }

}

?>
