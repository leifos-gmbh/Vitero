<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilViteroAppEventListener
{
    private static $instance;

    /**
     * @var ilViteroPlugin|null
     */
    private $plugin;

    /**
     * @var \ilLogger
     */
    private $logger;

    public function __construct()
    {
        global $DIC;

        $this->plugin = ilViteroPlugin::getInstance();
        $this->logger = $DIC->logger()->xvit();
    }

    /**
     * @return self
     */
    public static function getInstance() : ilViteroAppEventListener
    {
        if (!self::$instance instanceof ilViteroAppEventListener) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param int    $ref_id
     * @param string $event
     */
    public function storeEvent(int $ref_id, string $event) : void
    {
        $this->logger->info('Storing new event: ' . $event . ' (' . $ref_id . ')');
        foreach (ilViteroMaterialAssignments::lookupAssignmentsForRefId($ref_id) as $assignment) {
            $assignment->setSyncStatus(ilViteroMaterialAssignment::SYNC_STATUS_PENDING);
            $this->logger->info('Handling assignment with id: ' . $assignment->getAssignmentId());
            switch ($event) {
                case 'delete':
                case 'toTrash':
                    $this->logger->info('Handling event: ' . $event);
                    $assignment->setDeletedStatus(true);
                    break;
            }
            $assignment->save();
        }
    }
}