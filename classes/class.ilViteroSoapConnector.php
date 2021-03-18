<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Abstract vitero soap connector
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroSoapConnector.php 33586 2012-03-07 13:12:56Z smeyer $
 */
abstract class ilViteroSoapConnector
{
    const ERR_WSDL = 2001;

    const WS_TIMEZONE = 'Africa/Ceuta';
    const CONVERT_TIMZONE = 'Africa/Ceuta';
    const CONVERT_TIMEZONE_FIX = 'Africa/Ceuta';

    private $settings;
    private $plugin;

    protected $client = null;

    protected $logger = null;

    /**
     * @var ilProxySettings
     */
    protected $proxy;

    /**
     * Get instance
     */
    public function __construct()
    {
        global $DIC;

        $this->logger = $DIC->logger()->xvit();

        $this->plugin   = ilViteroPlugin::getInstance();
        $this->settings = ilViteroSettings::getInstance();
    }

    /**
     * @return ilLogger $logger
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * init soap client
     * @return void
     * @throws ilViteroConnectorException
     */
    protected function initClient()
    {
        try {
            $this->client = new SoapClient(
                $this->getSettings()->getServerUrl() . '/' . $this->getWsdlName(),
                array(
                    'cache_wsdl' => 0,
                    'trace'      => 1,
                    'exceptions' => true,
                    'classmap'   => ['phonetype' => 'ilViteroPhone']
                )
            );
            $this->client->__setSoapHeaders(
                $head = new ilViteroSoapWsseAuthHeader(
                    $this->getSettings()->getAdminUser(),
                    $this->getSettings()->getAdminPass()
                )
            );

            #$GLOBALS['ilLog']->write(__METHOD__. ': HEADER TO STRING : '. $head);
            return;
        } catch (SoapFault $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->error('Using wsdl: ' . $this->getSettings()->getServerUrl() . '/' . $this->getWsdlName());
            throw new ilViteroConnectorException($e->getMessage(), self::ERR_WSDL);
        }
    }

    /**
     * Get vitero settings
     * @return ilViteroSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Get wsdl name
     * @return string
     */
    abstract protected function getWsdlName();

    /**
     * @param Exception $e
     * @return int
     */
    protected function parseErrorCode(Exception $e) : int
    {
        if (is_object($e->detail->error)) {
            $this->getLogger()->debug('Found error code: ' . $e->detail->error->errorCode);
            return (int) $e->detail->error->errorCode;
        }
        // try error code
        if ($e->getCode() > 0) {
            return (int) $e->getCode();
        }
        return 0;

    }

    /**
     * @return ilViteroPlugin
     */
    public function getPluginObject()
    {
        return $this->plugin;
    }

    /**
     * Get soap client
     * @return SoapClient
     */
    public function getClient()
    {
        return $this->client;
    }

}

?>
