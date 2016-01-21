<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * 
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroSessionCodeSoapConnector.php 34871 2012-05-29 18:13:14Z smeyer $
 */
class ilViteroSessionCodeSoapConnector extends ilViteroSoapConnector
{
	const CODELENGTH = 16;
	const WSDL_NAME = 'sessioncode.wsdl';

	public function createPersonalGroupSessionCode($a_user_id, $a_group_id)
	{
		try {

			$this->initClient();

			// Wrap into single group object
			$sc = new stdClass();
			$sc->sessioncode = new stdClass();
			$sc->sessioncode->userid = (int) $a_user_id;
			$sc->sessioncode->groupid = (int) $a_group_id;
			$sc->sessioncode->allownotassignedusers = true;

			$code = $this->getClient()->createPersonalGroupSessionCode($sc);

			return $code->code;
		}
		catch(SoapFault $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Creating group session code  failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	public function createPersonalBookingSessionCode($a_vuser_id, $a_booking_id)
	{
		try {

			$this->initClient();

			$sc = new stdClass();
			$sc->sessioncode = new stdClass();
			$sc->sessioncode->userid = $a_vuser_id;
			$sc->sessioncode->bookingid = $a_booking_id;

			$GLOBALS['ilLog']->write(__METHOD__.': '. print_r($sc,true));

			$code = $this->getClient()->createPersonalBookingSessionCode($sc);
			return $code->code;
		}
		catch(SoapFault $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Creating group session code  failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	public function createVmsSessionCode($a_vuser_id, $a_group_id, ilDateTime $expires)
	{

		$GLOBALS['ilLog']->write('Creating new vms session code');

		try {
			$this->initClient();

			$req = new stdClass();
			$req->sessioncode = new stdClass();
			$req->sessioncode->userid = (int) $a_vuser_id;
			$req->sessioncode->groupid = (int) $a_group_id;
			$req->sessioncode->codelength = self::CODELENGTH;
			$req->sessioncode->expirationdate = $expires->get(IL_CAL_FKT_DATE,'YmdHi',self::CONVERT_TIMZONE);
			$req->sessioncode->timezone = self::WS_TIMEZONE;

			$reps = $this->getClient()->createVmsSessionCode($req);
			return $reps->code;
		}
		catch(SoapFault $e)  {
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Creating vms session code failed with message: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastResponse());
			throw new ilViteroConnectorException($e->getMessage(),$code);

		}
	}



	protected function getWsdlName()
	{
		return self::WSDL_NAME;
	}
}
?>