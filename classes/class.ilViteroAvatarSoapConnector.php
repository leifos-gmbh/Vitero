<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroAvatarSoapConnector.php 36242 2012-08-15 13:00:21Z smeyer $
 */
class ilViteroAvatarSoapConnector extends ilViteroSoapConnector
{
    const WSDL_NAME = 'mtom.wsdl';

    const FILE_TYPE_NORMAL = 0;
    const FILE_TYPE_SMILE = 1;

    /**
     * Overwrite
     * @var null|SoapClient
     */
    protected $client = null;

    public function __construct()
    {
        parent::__construct();
    }

    protected function getWsdlName()
    {
        return self::WSDL_NAME;
    }

    /**
     * Store avatar picture
     * @param int   $a_vuserid
     * @param array $a_file_info array('name' => filename, 'type' => 0|1, 'file' => path)
     */
    public function storeAvatar($a_vuserid, $a_file_info)
    {
        try {

            $this->initClient('cid:myid', $a_file_info['file']);

            $avatar         = new stdClass();
            $avatar->userid = $a_vuserid;

            $payload =
                '<ns1:storeAvatarRequest xmlns:ns1="http://www.vitero.de/schema/mtom">' .
                '<ns1:userid>' . $a_vuserid . '</ns1:userid>' .
                '<ns1:filename>' . $a_file_info['name'] . '</ns1:filename>' .
                '<ns1:type>' . $a_file_info['type'] . '</ns1:type>' .
                '<ns1:file><xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="cid:myid"/></ns1:file>' .
                '</ns1:storeAvatarRequest>';

            $message = new WSMessage(
                $payload,
                array(
                    'inputHeaders' =>
                        array(
                            ilViteroSoapWsseAuthHeader::getWSFHeader(
                                $this->getSettings()->getAdminUser(),
                                $this->getSettings()->getAdminPass()
                            )
                        ),
                    'attachments'  => array('myid' => file_get_contents($a_file_info['file']))
                )
            );

            $resp = $this->client->request($message);
        } catch (Exception $e) {

            $GLOBALS['ilLog']->write(__METHOD__ . ': ' . $e->getMessage());
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last Request: ' . $this->client->getLastRequest());
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last Response: ' . $this->client->getLastResponse());

            #throw new ilViteroConnectorException($e->getMessage(),0);
        }
    }

    protected function initClient($a_file_id, $a_file)
    {
        $clientConf = array(
            'to'          => $this->getSettings()->getServerUrl() . '/',
            'useSOAP'     => '1.1',
            'useMTOM'     => true,
            'responseXOP' => true,
            'CACert'      => '/home/stefan/public_html/certs/vitero/cert.pem'
        );

        if (ilViteroSettings::getInstance()->getMTOMCert()) {
            $clientConf['CACert'] = ilViteroSettings::getInstance()->getMTOMCert();
        }

        $this->client = new WSClient(
            $clientConf
        );
    }
}

?>