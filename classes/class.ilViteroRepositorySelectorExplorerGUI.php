<?php

/**
 * Class ilViteroRepositorySelectorInputGUI
 * Filters already selected nodes
 */
class ilViteroRepositorySelectorExplorerGUI extends ilRepositorySelectorExplorerGUI
{
    /**
     * @var ilViteroMaterialAssignments
     */
    private $assignments;

    /**
     * @param ilViteroMaterialAssignments $assignments
     */
    public function setAssignments(ilViteroMaterialAssignments $assignments) : void
    {
        $this->assignments = $assignments;
    }

    /**
     * @param mixed $a_node
     * @return bool
     */
    public function isNodeSelectable($a_node)
    {
        if (!parent::isNodeSelectable($a_node)) {
            return false;
        }
        foreach ($this->assignments->getAssignments() as $assignment) {
            if ($assignment->getRefId() == (int) $a_node['child']) {
                return false;
            }
        }
        return true;
    }
}