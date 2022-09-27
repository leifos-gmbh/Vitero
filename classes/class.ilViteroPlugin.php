<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

/**
 * Vitero repository object plugin
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id: class.ilViteroPlugin.php 39365 2013-01-21 15:55:26Z smeyer $
 */
class ilViteroPlugin extends ilRepositoryObjectPlugin
{
    const CTYPE = 'Services';
    const CNAME = 'Repository';
    const SLOT_ID = 'robj';
    const PNAME = 'Vitero';
    private static $instance = null;

    /**
     * Get singelton instance
     * @return ilViteroPlugin
     * @global ilPluginAdmin $ilPluginAdmin
     */
    public static function getInstance()
    {
        global $ilPluginAdmin;

        if (self::$instance) {
            return self::$instance;
        }
        include_once './Services/Component/classes/class.ilPluginAdmin.php';
        return self::$instance = ilPluginAdmin::getPluginObject(
            self::CTYPE,
            self::CNAME,
            self::SLOT_ID,
            self::PNAME
        );
    }

    /**
     * @inheritDoc
     * @param string $a_component
     * @param string $a_event
     * @param array  $a_parameter
     */
    public function handleEvent($a_component, $a_event, $a_parameter)
    {
        switch ($a_component) {
            case 'Services/Object':
                switch ($a_event) {
                    case 'toTrash':
                        if (isset($a_parameter['ref_id'])
                        ) {
                            $self = ilViteroAppEventListener::getInstance();
                            $self->storeEvent(
                                (int) $a_parameter['ref_id'],
                                (string) $a_event
                            );
                        }
                        break;

                    case 'delete':
                        $a_parameter['obj_type'] = $a_parameter['type'];
                    case 'update':
                        if (
                            isset($a_parameter['ref_id']) &&
                            isset($a_parameter['obj_type']) &&
                            strcmp($a_parameter['obj_type'], 'file') === 0
                        ) {
                            $self = ilViteroAppEventListener::getInstance();
                            $self->storeEvent(
                                (int) $a_parameter['ref_id'],
                                (string) $a_event
                            );
                        }
                        break;
                }
        }
    }


    /**
     * Auto load implementation
     * @param string class name
     */
    private final function autoLoad($a_classname)
    {
        $class_file = $this->getClassesDirectory() . '/class.' . $a_classname . '.php';
        @include_once($class_file);
    }

    /**
     * Init vitero
     */
    protected function init()
    {
        $this->initAutoLoad();
        // set configured log level
        foreach (ilLoggerFactory::getLogger('xvit')->getLogger()->getHandlers() as $handler) {
            $handler->setLevel(ilViteroSettings::getInstance()->getLogLevel());
        }
    }

    /**
     * Init auto loader
     * @return void
     */
    protected function initAutoLoad()
    {
        spl_autoload_register(
            array($this, 'autoLoad')
        );
    }

    /**
     * drop database tables and delete ilSetting entrys
     */
    protected function uninstallCustom()
    {
        global $ilDB;

        if ($ilDB->tableExists('rep_robj_xvit_data')) {
            $ilDB->dropTable('rep_robj_xvit_data');
        }

        if ($ilDB->tableExists('rep_robj_xvit_excl')) {
            $ilDB->dropTable('rep_robj_xvit_excl');
        }

        if ($ilDB->tableExists('rep_robj_xvit_locked')) {
            $ilDB->dropTable('rep_robj_xvit_locked');
        }

        if ($ilDB->tableExists('rep_robj_xvit_smap')) {
            $ilDB->dropTable('rep_robj_xvit_smap');
        }

        if ($ilDB->tableExists('rep_robj_xvit_umap')) {
            $ilDB->dropTable('rep_robj_xvit_umap');
        }

        $settings = new ilSetting('vitero_config');
        $settings->deleteAll();
    }

    public function getPluginName()
    {
        return "Vitero";
    }

    //Method called by external cron job plugin

    public function updateLearningProgress()
    {
        $vitero_lp = new ilViteroLearningProgress();
        #$vitero_lp->updateLearningProgress();
    }

    /**
     *
     */
    public function syncFiles()
    {
        global $DIC;

        $tree = $DIC->repositoryTree();
        $logger = $DIC->logger()->xvit();

        foreach (ilViteroMaterialAssignments::lookupPendingAssignments() as $assignment) {

            $obj_id = $assignment->getObjId();
            $ref_ids = ilObject::_getAllReferences($obj_id);
            $ref_id = end($ref_ids);

            if ($tree->isDeleted($ref_id)) {
                $logger->info('Ignoring update of deleted vitero object.');
                continue;
            }
            $vitero = ilObjectFactory::getInstanceByRefId($ref_id, false);
            if (!$vitero instanceof ilObjVitero) {
                $logger->warning('Cannot instatiate vitero object for ref_id: ' . $ref_id);
                continue;
            }
            $sync = new ilViteroFileSync($assignment, $vitero);
            $sync->sync();
        }
    }

}

?>