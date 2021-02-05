<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroUserMapping.php 32432 2012-01-02 12:00:13Z smeyer $
 */
class ilViteroSessionStore
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * @return ilViteroSessionStore
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return bool
     */
    public function deleteExpiredSessions()
    {
        global $ilDB;

        $query = 'DELETE FROM rep_robj_xvit_smap' .
            ' WHERE expirationdate < ' . $ilDB->quote(time(), 'integer');
        $ilDB->manipulate($query);
        return true;
    }

    /**
     * @param integer $a_vitero_user
     * @return string[]
     */
    public function getSessionsByUser($a_vitero_user)
    {
        global $ilDB;

        $query = 'SELECT vsession FROM rep_robj_xvit_smap' .
            ' WHERE usr_id = ' . $ilDB->quote($a_vitero_user, 'integer');
        $res   = $ilDB->query($query);
        $ret   = array();

        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $ret[] = (string) $row->vsession;
        }

        return $ret;
    }

    /**
     * @param string[] $a_sessions
     * @return bool
     */
    public function deleteSessions(array $a_sessions)
    {
        global $ilDB;

        $query = 'DELETE FROM rep_robj_xvit_smap ' .
            ' WHERE ' . $ilDB->in('vsession', $a_sessions, false, 'text') .
            ' AND expirationdate < ' . $ilDB->quote(time(), 'integer');
        $ilDB->manipulate($query);
        return true;
    }

    /**
     * @param integer $a_vitero_user
     * @param string  $a_session
     * @param         $a_expirationdate
     * @param integer $a_type
     * @return bool
     */
    public function addSession($a_vitero_user, $a_session, $a_expirationdate, $a_type)
    {
        global $ilDB;

        $query = 'INSERT INTO rep_robj_xvit_smap (usr_id,vsession,expirationdate,vtype) ' .
            'VALUES( ' .
            $ilDB->quote($a_vitero_user, 'integer') . ', ' .
            $ilDB->quote($a_session, 'text') . ', ' .
            $ilDB->quote($a_expirationdate, 'integer') . ', ' .
            $ilDB->quote($a_type, 'integer') . ' ' .
            ')';
        $ilDB->manipulate($query);
        return true;
    }
}

?>
