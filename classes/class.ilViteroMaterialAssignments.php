<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Handles the locked users
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilViteroMaterialAssignments
{
    /**
     * @var ilViteroMaterialAssignments[]
     */
    private static $instances;

    /**
     * @var ilViteroMaterialAssignment[]
     */
    private $assignments;

    /**
     * @var int
     */
    private $obj_id;

    /**
     * @var ilDBInterface
     */
    private $db;

    /**
     * ilViteroMaterialAssignments constructor.
     */
    protected function __construct(int $obj_id)
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->obj_id = $obj_id;
        $this->read();
    }

    /**
     * @param int $obj_id
     * @return ilViteroMaterialAssignments
     */
    public static function getInstanceByObjId(int $obj_id) : ilViteroMaterialAssignments
    {
        if (!isset(self::$instances[$obj_id])) {
            self::$instances[$obj_id] = new self($obj_id);
        }
        return self::$instances[$obj_id];
    }

    /**
     * @param int $ref_id
     * @return ilViteroMaterialAssignment[]
     * @throws ilDatabaseException
     */
    public static function lookupAssignmentsForRefId(int $ref_id) : array
    {
        global $DIC;

        $db = $DIC->database();

        $query = 'select assignment_id from ' . ilViteroMaterialAssignment::TABLE_NAME . ' ' .
            'where ref_id = ' . $db->quote($ref_id, ilDBConstants::T_INTEGER);
        $res = $db->query($query);

        ilLoggerFactory::getLogger('xvit')->info($query);

        $assignments = [];
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $assignments[] = new ilViteroMaterialAssignment($row->assignment_id);
        }
        return $assignments;
    }


    /**
     * @return ilViteroMaterialAssignment[]
     */
    public function getAssignments() : array
    {
        return $this->assignments;
    }

    protected function read() : void
    {
        $query = 'select assignment_id from ' . ilViteroMaterialAssignment::TABLE_NAME . ' ' .
            'where obj_id = ' . $this->db->quote($this->obj_id, ilDBConstants::T_INTEGER);
        $res = $this->db->query($query);
        $this->assignments = [];
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->assignments[] = new ilViteroMaterialAssignment($row->assignment_id);
        }
    }
}
