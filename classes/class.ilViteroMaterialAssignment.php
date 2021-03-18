<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Handles the locked users
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilViteroMaterialAssignment
{
    /**
     * @var int
     */
    public const SYNC_STATUS_UNDEFINED = 0;
    public const SYNC_STATUS_PENDING = 1;
    public const SYNC_STATUS_SYNCHRONISED = 2;

    /**
     * var int
     */
    public const TYPE_REFERENCE = 1;
    public const TYPE_DIRECT_ASSIGNMENT = 2;
    public const TYPE_DEFAULT = self::TYPE_REFERENCE;

    public const TABLE_NAME = 'rep_robj_xvit_mat';

    /**
     * @var int
     */
    private $assignment_id;

    /**
     * @var int
     */
    private $obj_id;

    /**
     * @var ?int
     */
    private $ref_id;

    /**
     * @var ?int
     */
    private $vitero_id;

    /**
     * @var ?string
     */
    private $title;

    /**
     * @var int
     */
    private $sync_status;

    /**
     * @var bool
     */
    private $deleted_status = false;

    /**
     * @var ilDBInterface
     */
    private $db;

    /**
     * @var bool
     */
    private $exists = false;


    /**
     * ilViteroMaterialAssignment constructor.
     * @param int $assignment_id
     * @param int $obj_id
     *
     */
    public function __construct(?int $assignment_id = null)
    {
        global $DIC;

        $this->assignment_id = $assignment_id;

        $this->db = $DIC->database();
        $this->read();
    }
    /**
     * @return int
     */
    public function getObjId() : int
    {
        return $this->obj_id;
    }

    /**
     * @param int $obj_id
     */
    public function setObjId(int $obj_id) : void
    {
        $this->obj_id = $obj_id;
    }

    /**
     * @return int|null
     */
    public function getViteroId() : ?int
    {
        return $this->vitero_id;
    }

    /**
     * @param int|null $vitero_id
     */
    public function setViteroId(?int $vitero_id) : void
    {
        $this->vitero_id = $vitero_id;
    }


    /**
     * @return int
     */
    public function getAssignmentId() : int
    {
        return $this->assignment_id;
    }

    /**
     * @param int $assignment_id
     */
    public function setAssignmentId(int $assignment_id) : void
    {
        $this->assignment_id = $assignment_id;
    }

    /**
     * @return int|null
     */
    public function getRefId() : ?int
    {
        return $this->ref_id;
    }

    /**
     * @param int|null $ref_id
     */
    public function setRefId(?int $ref_id) : void
    {
        $this->ref_id = $ref_id;
    }

    /**
     * @return bool
     */
    public function isReference() : bool
    {
        return $this->getRefId() !== null;
    }

    /**
     * @return string|null
     */
    public function getTitle() : ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title) : void
    {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getSyncStatus() : int
    {
        return $this->sync_status;
    }

    /**
     * @param int $sync_status
     */
    public function setSyncStatus(int $sync_status) : void
    {
        $this->sync_status = $sync_status;
    }

    /**
     * @return bool
     */
    public function isDeletedStatus() : bool
    {
        return $this->deleted_status;
    }

    /**
     * @param bool $deleted_status
     */
    public function setDeletedStatus(bool $deleted_status) : void
    {
        $this->deleted_status = $deleted_status;
    }



    /**
     * @
     */
    public function delete()
    {
        $query = 'delete from ' . self::TABLE_NAME . ' ' .
            'where assignment_id = ' . $this->db->quote($this->getAssignmentId(), ilDBConstants::T_INTEGER);
        $this->db->manipulate($query);
    }

    public function read() : void
    {
        if (!$this->assignment_id) {
            return;
        }

        $query = 'select * from ' . self::TABLE_NAME . ' ' .
            'where assignment_id = ' . $this->db->quote($this->getAssignmentId(), ilDBConstants::T_INTEGER);
        $res = $this->db->query($query);
        $this->exists = false;
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->exists = true;
            $this->setObjId($row->obj_id);
            $this->setViteroId($row->vit_id);
            $this->setRefId($row->ref_id);
            $this->setTitle($row->title);
            $this->setSyncStatus($row->sync_status);
            $this->setDeletedStatus($row->deleted_status);
        }
    }

    public function save() : void
    {
        if ($this->exists) {
            $this->update();
        } else {
            $this->create();
        }
    }

    protected function create() : void
    {
        $this->setAssignmentId($this->db->nextId(self::TABLE_NAME));
        $query = 'insert into ' . self::TABLE_NAME . ' ' .
            '(assignment_id, obj_id, ref_id, vit_id, title,sync_status, deleted_status) ' .
            'values ( ' .
            $this->db->quote($this->getAssignmentId(), ilDBConstants::T_INTEGER) . ',  ' .
            $this->db->quote($this->getObjId(), ilDBConstants::T_INTEGER) . ', ' .
            $this->db->quote($this->getRefId(), ilDBConstants::T_INTEGER) . ', ' .
            $this->db->quote($this->getViteroId(), ilDBConstants::T_INTEGER) . ', ' .
            $this->db->quote($this->getTitle(), ilDBConstants::T_TEXT) . ', ' .
            $this->db->quote($this->getSyncStatus(), ilDBConstants::T_INTEGER) . ', ' .
            $this->db->quote($this->isDeletedStatus(), ilDBConstants::T_INTEGER) . ' ' .
            ')';
        $this->db->query($query);
        $this->exists = true;
    }

    protected function update()
    {
        $query = 'update ' . self::TABLE_NAME . ' ' .
            'set ' .
            'obj_id = ' . $this->db->quote($this->getObjId(), ilDBConstants::T_INTEGER) . ', ' .
            'ref_id = ' . $this->db->quote($this->getRefId(), ilDBConstants::T_INTEGER) . ', ' .
            'vit_id = ' . $this->db->quote($this->getViteroId(), ilDBConstants::T_INTEGER) . ', ' .
            'title = ' . $this->db->quote($this->getTitle(), ilDBConstants::T_TEXT) . ', ' .
            'sync_status = ' . $this->db->quote($this->getSyncStatus(), ilDBConstants::T_INTEGER) . ', ' .
            'deleted_status = ' . $this->db->quote($this->isDeletedStatus(), ilDBConstants::T_INTEGER) . ' ' .
            'where assignment_id = ' . $this->db->quote($this->getAssignmentId(), ilDBConstants::T_INTEGER);
        $this->db->manipulate($query);
    }

}
