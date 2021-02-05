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
     * load avatar
     * @param <type> $a_vuserid
     */
    public function loadAvatar($a_vuserid)
    {
        try {

            $this->initClient();

            $user = new stdClass();
            #$user->userid = $a_vuserid;
            #$user->type = 0;
            #$user->size = 2;

            #$ret = $this->getClient()->loadAvatar($user);

            $file         = new stdClass();
            $file->nodeid = 51;

            $ret = $this->getClient()->loadFile($file);

            var_dump($ret);
            ob_end_flush();

        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Loading avatar failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last response: ' . $this->getClient()->__getLastResponse());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }
    }

    public function storeAvatar()
    {
        try {

            $this->initClient();

            $ava           = new stdClass();
            $ava->userid   = 5;
            $ava->filename = 'User.jpg';
            $ava->type     = 0;
            $ava->file     = '<xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="http://localhost/~stefan/images.j" \>';

            $ret = $this->getClient()->storeAvatar($ava);

            var_dump($ret);
            ob_end_flush();

        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Storing avatar failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last response: ' . $this->getClient()->__getLastResponse());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }
    }

}

?>
