<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilViteroMaterialAssignmentTableGUI
 */
class ilViteroMaterialAssignmentTableGUI extends ilTable2GUI
{
    private const VMA_ID = 'xvit_ma';

    /**
     * @var ilObject
     */
    private $vitero;

    /**
     * @var ilViteroSettings
     */
    private $settings;

    /**
     * @var ilViteroPlugin
     */
    private $plugin;

    /**
     * @var \ILIAS\UI\Factory
     */
    private $ui_factory;

    /**
     * @var \ILIAS\UI\Renderer
     */
    private $ui_renderer;

    /**
     * ilViteroMaterialAssignmentTableGUI constructor.
     */
    public function __construct(ilObjViteroGUI $gui, ilObjVitero $vitero, string $command)
    {
        global $DIC;

        $this->setId(self::VMA_ID .  $vitero->getId());
        parent::__construct($gui, $command);

        $this->vitero = $vitero;
        $this->settings = ilViteroSettings::getInstance();
        $this->plugin = ilViteroPlugin::getInstance();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
    }

    /**
     * Init table
     */
    public function init() : void
    {
        $this->setTitle($this->plugin->txt('filemanager_assignment_table'));

        $this->setFormName('materials');
        $this->setFormAction($this->ctrl->getFormAction($this->getParentObject(), $this->getParentCmd()));

        $this->addColumn("", "f", 1);
        $this->addColumn($this->lng->txt('title'), 'title', '60%');
        $this->addColumn($this->plugin->txt('filemanager_assignment_table_referenced'), 'referenced', '10%');
        $this->addColumn($this->plugin->txt('filemanager_assignment_table_sync_status'), 'sync', '10%');
        $this->addColumn($this->plugin->txt('filemanager_assignment_table_deleted'), 'deleted', '10%');
        $this->setSelectAllCheckbox('files');
        $this->setRowTemplate("tpl.show_materials_row.html", substr($this->plugin->getDirectory(), 2));

        $this->addMultiCommand('materialsDeleteConfirmation', $this->lng->txt('delete'));
    }

    public function parse(ilViteroMaterialAssignments $assignments)
    {
        $rows = [];
        foreach ($assignments->getAssignments() as $assignment) {
            if ($assignment->isReference() && !$assignment->isDeletedStatus()) {
                $row['title'] = ilObject::_lookupTitle(ilObject::_lookupObjId($assignment->getRefId()));
            } elseif ($assignment->isDeletedStatus()) {
                $row['title'] = $this->plugin->txt('filemanager_deleted');
            } else {
                $row['title'] = $assignment->getTitle();
            }
            $row['ref_id']  = $assignment->getRefId();
            $row['sync'] = $assignment->getSyncStatus();
            $row['deleted'] = $assignment->isDeletedStatus();
            $row['assignment_id'] = $assignment->getAssignmentId();

            $rows[] = $row;
        }
        $this->setData($rows);
    }

    /**
     * @inheritDoc
     */
    public function fillRow($row)
    {
        $this->tpl->setVariable('VAL_ID', $row['assignment_id']);
        $this->tpl->setVariable('VAL_SYNCED', $this->plugin->txt('filemanager_sync_status_' . $row['sync']));
        if ($row['ref_id'] && !$row['deleted']) {
            $this->tpl->setVariable('VAL_TITLE_HREF', ilLink::_getLink($row['ref_id']));
            $this->tpl->setVariable('VAL_TITLE_LINK', $row['title']);

            $path_gui = new ilPathGUI();
            $path_gui->enableTextOnly(false);
            $this->tpl->setVariable('PATH', $path_gui->getPath(ROOT_FOLDER_ID, $row['ref_id']));
            $this->tpl->setVariable('VAL_REFERENCED' ,
                $this->ui_renderer->render(
                    $this->ui_factory->symbol()->glyph()->apply()
                ));
        } else {
            $this->tpl->setVariable('VAL_TITLE', $row['title']);
        }
        if ($row['deleted']) {
            $this->tpl->setVariable('VAL_DELETED' ,
                $this->ui_renderer->render(
                    $this->ui_factory->symbol()->glyph()->apply()
                ));
        }
    }
}