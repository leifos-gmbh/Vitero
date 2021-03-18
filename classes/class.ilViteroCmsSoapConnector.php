<?php

class ilViteroCmsSoapConnector extends ilViteroSoapConnector
{
    /**
     * @var int
     */
    public const FOLDER_AGENDA_ID = 1;

    /**
     * @var string
     */
    public const FOLDER_AGENDA_NAME = 'Agenda';

    /**
     * @var int
     */
    public const FOLDER_MEDIA_ID = 3;

    /**
     * @var string
     */
    public const FOLDER_MEDIA_NAME = 'Medien';

    /**
     * @var int
     */
    public const FOLDER_WELCOME_ID = 5;

    /**
     * @var string
     */
    public const FOLDER_WELCOME_NAME = 'Welcome';


    public const WSDL_NAME = 'cms.wsdl';

    protected function getWsdlName()
    {
        return self::WSDL_NAME;
    }

    /**
     * @param int $folder_id
     * @return bool
     * @throws ilViteroConnectorException
     */
    public function findFolder(int $group_id, int $folder_id) : bool
    {
        $this->initClient();

        $request = new stdClass();
        $request->groupid = $group_id;

        try  {
            $response = $this->getClient()->getGroupFolder($request);
            $this->logger->dump($response);
            return $this->folderIdExists($response->folder, $folder_id);
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $this->getLogger()->error('getGroupFolder failed with message code: ' . $code);
            $this->getLogger()->error($this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }
    }

    public function findGroupFolderId(int $group_id) : int
    {
        $this->initClient();

        $request = new stdClass();
        $request->groupid = $group_id;

        try  {
            $response = $this->getClient()->getGroupFolder($request);
            $this->getLogger()->emergency('Node id: ' . $response->folder->nodeid);
            return (int) $response->folder->nodeid;
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $this->getLogger()->error('getGroupFolder failed with message code: ' . $code);
            $this->getLogger()->error($this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }

    }

    /**
     * @param int    $parent_id
     * @param int    $type
     * @param string $name
     * @return int
     * @throws ilViteroConnectorException
     */
    public function createFolder(int $parent_id, int $type, string $name) : int
    {

        $request = new stdClass();
        $request->nodeid = $this->findGroupFolderId($parent_id);
        $request->name = $name;
        $request->displaytype = $type;
        $request->visible = true;


        try  {

            $this->initClient();
            $response = $this->getClient()->createFolder($request);
            return (int) $response->nodeid;
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $this->getLogger()->error('createFolder failed with message code: ' . $code);
            $this->getLogger()->error($this->getClient()->__getLastRequest());
            $this->getLogger()->dump($request, ilLogLevel::ERROR);
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }

    }

    public function deleteNode(int $nodeid)
    {
        $this->initClient();
        $request = new stdClass();
        $request->nodeid = $nodeid;

        try  {
            $response = $this->getClient()->deleteNode($request);
            return (int) $response->nodeid;
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $this->getLogger()->error('delete node failed with message code: ' . $code);
            $this->getLogger()->error($this->getClient()->__getLastRequest());
            $this->getLogger()->dump($request, ilLogLevel::ERROR);
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }

    }

    /**
     * REcursive check for folder_id
     * @param     $folder
     * @param int $folder_id
     * @return bool
     */
    private function folderIdExists($folder, int $folder_id)
    {
        $this->logger->dump($folder, ilLogLevel::DEBUG);
        if (isset($folder->nodeid)) {
            if ($folder->nodeid == $folder_id) {
                $this->getLogger()->info('Folder id exists');
                return true;
            }
        }
        if (is_object($folder->children)) {
            if (is_object($folder->children->folder)) {
                return $this->folderIdExists($folder->children->folder, $folder_id);
            }
            if (is_array($folder->children->folder)) {
                foreach ($folder->children->folder as $idx => $child_folder) {
                    if ($this->folderIdExists($child_folder, $folder_id)) {
                        $this->getLogger()->debug('Found folder id');
                        return true;
                    }
                }
            }
        }
        $this->getLogger()->info('Folder not found');
        return false;
    }
}