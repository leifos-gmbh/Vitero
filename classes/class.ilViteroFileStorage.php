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
class ilViteroFileStorage extends ilFileSystemStorage
{
    /**
     * @var string
     */
    private const PREFIX = 'ilVitero';
    private const POSTFIX = 'xvit';

    private $logger;

    public function __construct(int $a_container_id = 0)
    {
        global $DIC;

        $this->logger = $DIC->logger()->xvit();
        parent::__construct(ilFileSystemStorage::STORAGE_DATA, false, $a_container_id);
    }


    /**
     * @return string
     */
    protected function getPathPrefix()
    {
        return self::PREFIX;
    }

    /**
     * @return string
     */
    protected function getPathPostfix()
    {
        return self::POSTFIX;
    }

    public function handleUpload(FileUpload $upload, string $tmpname, int $assignment_id) : void
    {
        if ($upload->hasUploads() && !$upload->hasBeenProcessed()) {
            try {
                $upload->process();
            } catch (IllegalStateException $e) {
                $this->logger->warning('File upload already processed: ' . $e->getMessage());
            }
        }

        $result = isset($upload->getResults()[$tmpname]) ? $upload->getResults()[$tmpname] : false;
        if ($result instanceof UploadResult && $result->isOK() && $result->getSize()) {
            $upload->moveOneFileTo(
                $result,
                $this->getPathPrefix() . '/' . $this->getPathPostfix() . '_' . $this->getContainerId(),
                Location::STORAGE,
                (string) $assignment_id
            );
        }
    }

}