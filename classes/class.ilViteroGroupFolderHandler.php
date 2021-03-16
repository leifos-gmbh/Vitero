<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Handles vitero file storage
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilViteroGroupFolderHandler
{
    private $logger;

    /**
     * @var ilObjVitero
     */
    private $vitero;

    /**
     * ilViteroGroupFolderHandler constructor.
     * @param ilObjVitero $vitero
     */
    public function __construct(ilObjVitero $vitero)
    {
        global $DIC;

        $this->logger = ilLoggerFactory::getLogger('xvit');
        $this->vitero = $vitero;
    }

    /**
     * @param int $type
     */
    public function initFolderType(int $type) : void
    {
        $stored_id = 0;
        $name = '';
        switch ($type) {
            case ilViteroCmsSoapConnector::FOLDER_MEDIA_ID:
                $stored_id = $this->vitero->getFolderMediaId();
                $name = ilViteroCmsSoapConnector::FOLDER_MEDIA_NAME;
                break;
            case ilViteroCmsSoapConnector::FOLDER_AGENDA_ID:
                $stored_id = $this->vitero->getFolderAgendaId();
                $name = ilViteroCmsSoapConnector::FOLDER_AGENDA_NAME;
                break;
            case ilViteroCmsSoapConnector::FOLDER_WELCOME_ID:
                $stored_id = $this->vitero->getFolderWelcomeId();
                $name = ilViteroCmsSoapConnector::FOLDER_WELCOME_NAME;
                break;
        }
        if ($stored_id) {
            try {
                $connector = new ilViteroCmsSoapConnector();
                $res = $connector->findFolder($this->vitero->getVGroupId(),$stored_id);
                if (!$res) {
                    // folder does not exist anymore
                    $stored_id = 0;
                }

            } catch (ilViteroConnectorException $e) {

            }
        }
        if (!$stored_id) {
            try {
                $connector = new ilViteroCmsSoapConnector();
                $node_id = $connector->createFolder($this->vitero->getVGroupId(), $type, $name);
                switch ($type) {
                    case ilViteroCmsSoapConnector::FOLDER_MEDIA_ID:
                        $this->vitero->setFolderMediaId($node_id);
                        break;
                    case ilViteroCmsSoapConnector::FOLDER_AGENDA_ID:
                        $this->vitero->setFolderAgendaId($node_id);
                        break;
                    case ilViteroCmsSoapConnector::FOLDER_WELCOME_ID:
                        $this->vitero->setFolderWelcomeId($node_id);
                        break;
                }
                $this->vitero->update();
            } catch (ilViteroConnectorException $e) {

            }
        }
    }
}
?>