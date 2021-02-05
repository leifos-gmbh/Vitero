<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Calendar/interfaces/interface.ilDatePeriod.php';

/**
 * Description of class
 * @author  Stefan Meyer <meyer@leifos.com>
 * @ingroup Expression CURSOR is undefined on line 8, column 15 in Templates/Scripting/PHPClass.php.
 */
class ilViteroBookingDatePeriod implements ilDatePeriod
{

    private $start = null;
    private $end = null;
    private $fullday = null;

    public function __construct($booking)
    {
        $this->start = ilViteroUtils::parseSoapDate($booking->start);
        $this->end   = ilViteroUtils::parseSoapDate($booking->end);

        if ($booking->cafe) {
            $this->fullday = true;
        } else {
            $this->fullday = false;
        }
    }

    /**
     * Interface method get start
     * @access public
     * @return ilDateTime
     * @static
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Interface method get end
     * @access public
     * @return ilDateTime
     * @static
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * is fullday
     * @access public
     * @return bool fullday or not
     */
    public function isFullday()
    {
        return (bool) $this->fullday;
    }

}

?>
