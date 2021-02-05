<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilViteroBookingCode
{
    private $vgroup_id = 0;
    private $booking_id = 0;
    private $code = '';

    private $exists = false;

    /**
     * @var ilDBInterface
     */
    private $db;

    public function __construct($vgroup_id = 0, $booking_id = 0)
    {
        global $DIC;

        $this->db = $DIC->database();

        $this->vgroup_id  = $vgroup_id;
        $this->booking_id = $booking_id;
        $this->read();
    }

    private function read()
    {
        $query = 'SELECT * FROM rep_robj_xvit_codes ' .
            'WHERE vgroup_id = ' . $this->db->quote($this->getVgroupId(), 'integer') . ' ' .
            'AND booking_id = ' . $this->db->quote($this->getBookingId(), 'integer');
        $res   = $this->db->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->exists = true;
            $this->code   = $row->code;
        }
    }

    public function getVgroupId()
    {
        return $this->vgroup_id;
    }

    /**
     * get booking id
     * @return int
     */
    public function getBookingId()
    {
        return $this->booking_id;
    }

    public static function deleteByVgroup($a_vgroup)
    {
        global $DIC;

        $db = $DIC->database();

        $query = 'DELETE from rep_robj_xvit_codes ' .
            'WHERE vgroup_id = ' . $db->quote($a_vgroup, 'integer');
        $db->manipulate($query);
    }

    public function save()
    {
        if ($this->exists()) {
            return $this->update();
        }
        return $this->insert();
    }

    public function exists()
    {
        return $this->exists;
    }

    public function update()
    {
        $query = 'UPDATE rep_robj_xvit_codes ' .
            'SET code = ' . $this->db->quote($this->getCode(), 'text') . ' ' .
            'WHERE booking_id = ' . $this->db->quote($this->getBookingId(), 'integer') . ' ' .
            'AND vgroup_id = ' . $this->db->quote($this->getVgroupId(), 'integer');
        $this->db->manipulate($query);
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($a_code)
    {
        $this->code = $a_code;
    }

    public function insert()
    {
        $query = 'INSERT INTO rep_robj_xvit_codes (vgroup_id, booking_id, code) ' .
            'VALUES( ' .
            $this->db->quote($this->getVgroupId(), 'integer') . ', ' .
            $this->db->quote($this->getBookingId(), 'integer') . ', ' .
            $this->db->quote($this->getCode(), 'text') . ' ' .
            ')';
        $this->db->manipulate($query);
    }

    public function delete()
    {
        $query = 'DELETE from rep_robj_xvit_codes ' .
            'WHERE booking_id = ' . $this->db->quote($this->getBookingId(), 'integer') . ' ' .
            'AND vgroup_id = ' . $this->db->quote($this->getVgroupId(), 'integer');
        $this->db->manipulate($query);
    }
}

?>