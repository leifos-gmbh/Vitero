<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroMTOMSoapConnector.php 31663 2011-11-14 15:29:13Z smeyer $
 */
class ilViteroMTOMSoapConnector extends ilViteroSoapConnector
{
    const WSDL_NAME = 'mtom.wsdl';

    protected function getWsdlName()
    {
        return self::WSDL_NAME;
    }

    /**
     * @param int    $folder_id
     * @param string $name
     * @param string $path
     * @throws ilViteroConnectorException
     */
    public function storeFile(int $folder_id, string $name, string $path)
    {
        $this->initClient();

        $file = new stdClass();
        $file->filename = $name;
        $file->foldernodeid = $folder_id;
        $file->file = file_get_contents($path);

        try {
            $response = $this->getClient()->storeFile($file);
            return $response->nodeid;
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->error($this->getClient()->__getLastResponse());
            $this->getLogger()->error($this->getClient()->__getLastResponseHeaders());
            throw new ilViteroConnectorException(
                $e->getMessage(),
                $code
            );
        }
    }

    protected function initClient()
    {
        $this->getLogger()->dump($this->getSettings()->getServerUrl() . '/' . $this->getWsdlName());
        $this->client = new ilViteroMultiPartSoapClient(
            $this->getSettings()->getServerUrl() . '/' . $this->getWsdlName(),
                [
                    'soap_version' => SOAP_1_1,
                    'cache_wsdl' => 0,
                    'trace'      => 1,
                    'exceptions' => true,
                ]
            );
            $this->client->__setSoapHeaders(
                $head = new ilViteroSoapWsseAuthHeader(
                    $this->getSettings()->getAdminUser(),
                    $this->getSettings()->getAdminPass()
                )
            );
    }

}

?>
