<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;

/**
 * Handles vitero file storage
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilViteroFileSync
{
    /**
     * @var ilObjVitero
     */
    private $vitero;

    /**
     * @var ilViteroMaterialAssignment
     */
    private $material;

    /**
     * @var ilViteroMTOMSoapConnector
     */
    private $connector;

    /**
     * @var ilLogger
     */
    private $logger;

    public function __construct(ilViteroMaterialAssignment $assignment, ilObjVitero $vitero)
    {
        global $DIC;

        $this->assignment = $assignment;
        $this->vitero = $vitero;
        $this->logger = $DIC->logger()->xvit();
    }

    public function sync() : void
    {
        if ($this->handleDeletedFiles()) {
            return;
        }
        $this->handleFileSync();

        $this->determineAbsolutePath();
    }

    public function syncFileToFolder(int $folder_type) : void
    {
        $path = $this->determineAbsolutePath();
        $name = $this->determineName();

        $folder_id = 0;
        switch ($folder_type) {
            case ilViteroCmsSoapConnector::FOLDER_MEDIA_ID:
                $folder_id = $this->vitero->getFolderMediaId();
                break;
            case ilViteroCmsSoapConnector::FOLDER_WELCOME_ID:
                $folder_id = $this->vitero->getFolderWelcomeId();
                break;
        }

        try {
            $connector = new ilViteroMTOMSoapConnector();
            $new_id = $connector->storeFile(
                $folder_id,
                $name,
                $path
            );
            $this->assignment->setViteroId($new_id);
            $this->assignment->setSyncStatus(ilViteroMaterialAssignment::SYNC_STATUS_SYNCHRONISED);
            $this->assignment->save();
        } catch (ilViteroConnectorException $e) {
            $this->logger->error('Error syncing file: ' . $e->getMessage());
        }
        return;
    }


    public function determineAbsolutePath() : string
    {
        if ($this->assignment->isReference()) {
            $file = ilObjectFactory::getInstanceByRefId($this->assignment->getRefId());
            if ($file instanceof ilObjFile) {
                return $file->getFile();
            }
        }
        if (!$this->assignment->isReference()) {
            $storage = new ilViteroFileStorage($this->assignment->getObjId());
            return $storage->getAbsolutePath() . '/' . $this->assignment->getAssignmentId();
        }
    }

    public function determineName()
    {
        if ($this->assignment->isReference()) {
            $file = ilObjectFactory::getInstanceByRefId($this->assignment->getRefId());
            if ($file instanceof ilObjFile) {
                return $file->getFileName();
            }
        }
        if (!$this->assignment->isReference()) {
            return $this->assignment->getTitle();
        }
    }

    protected function handleDeletedFiles() : bool
    {
        if (!$this->assignment->isDeletedStatus()) {
            return false;
        }
        if (!$this->assignment->isReference()) {
            $this->logger->info('Deleting non referenced file assignment');
            $vitero_file_storage = new ilViteroFileStorage($this->assignment->getObjId());
            $vitero_file_storage->deleteFile($vitero_file_storage->getAbsolutePath(). '/' . $this->assignment->getAssignmentId());
            $this->logger->debug('Deleted: ' . $vitero_file_storage->getAbsolutePath(). '/' . $this->assignment->getAssignmentId());
            $this->deleteNode($this->assignment->getViteroId());
            $this->assignment->delete();
            return true;
        }
        if ($this->assignment->isReference()) {
            // only delete assignment and remote file
            $this->logger->info('Delete reference assignment');
            $this->deleteNode($this->assignment->getViteroId());
            $this->assignment->delete();
            return true;
        }
        return false;
    }

    protected function deleteNode(?int $nodeid)  : void
    {
        if (!$nodeid) {
            $this->logger->warning('trying to delete empty node');
            return;
        }

        try {
            $connector = new ilViteroCmsSoapConnector();
            $connector->deleteNode($nodeid);
        } catch (ilViteroConnectorException $e) {
            $this->logger->error('Delete faled with message: ' . $e->getMessage());
        }
    }

    /**
     *
     */
    protected function handleFileSync() : bool
    {
        if ($this->assignment->setSyncStatus() != ilViteroMaterialAssignment::SYNC_STATUS_PENDING) {
            return false;
        }
        return true;
    }

}
