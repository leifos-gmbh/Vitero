<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * 
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroUserSoapConnector.php 56608 2014-12-19 10:11:57Z fwolf $
 */
class ilViteroUserSoapConnector extends ilViteroSoapConnector
{
	const WSDL_NAME = 'user.wsdl';

	protected $available_locales = array("en", "de");

	public function getUser($a_user_id)
	{
		try {

			$this->initClient();

			// Wrap into single group object
			$us = new stdClass();
			$us->userid = $a_user_id;
			$user = $this->getClient()->getUser($us);

			return $user;
		}
		catch(Exception $e)
		{
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Get user failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	/**
	 * Vreate new vitero user
	 * @param ilObjUser $iu
	 * @return <type>
	 */
	public function createUser(ilObjUser $iu)
	{
		try {

			$this->initClient();

			$user = new stdClass();
			$user->user = new stdClass();
			$user->user->password = ilViteroUtils::randPassword();
			$user->user->customeridlist = ilViteroSettings::getInstance()->getCustomer();

			$this->loadFromUser($user->user,$iu);

			$nuser = $this->getClient()->createUser($user);
			return $nuser->userid;

		}
		catch(SoapFault $e)
		{
			$this->getLogger()->warning('Calling webservice failed with message: ' . $e->getMessage().' with code: ' . $e->getCode());
			if($this->shouldRetryCall($e))
			{
				$this->getLogger()->info('Retrying soap call.');
				return $this->createUser($iu);
			}
			
			
			
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Create user failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	public function updateUser($a_vuserid, ilObjUser $iu)
	{
		try {

			$this->initClient();

			$user = new stdClass();
			$user->user = new stdClass();
			$user->user->id = (int) $a_vuserid;
			
			$this->loadFromUser($user->user, $iu);

			$this->getClient()->updateUser($user);

		}
		catch(SoapFault $e)
		{
			$this->getLogger()->warning('Calling webservice failed with message: ' . $e->getMessage().' with code: ' . $e->getCode());
			if($this->shouldRetryCall($e))
			{
				$this->getLogger()->info('Retrying soap call.');
				return $this->updateUser($a_vuserid,$iu);
			}
			
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Update user failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}

	public function deleteUser($a_vuserid)
	{
		try {
			$this->initClient();

			$user = new stdClass();
			$user->userid = $a_vuserid;

			$this->getClient()->deleteUser($user);
		}
		catch(SoapFault $e)
		{
			$this->getLogger()->warning('Calling webservice failed with message: ' . $e->getMessage().' with code: ' . $e->getCode());
			if($this->shouldRetryCall($e))
			{
				$this->getLogger()->info('Retrying soap call.');
				return $this->deleteUser($a_vuserid);
			}
			
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Delete user failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
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
			$user->userid = $a_vuserid;

			$ret = $this->getClient()->loadAvatar($user);

		}
		catch(SoapFault $e)
		{
			$this->getLogger()->warning('Calling webservice failed with message: ' . $e->getMessage().' with code: ' . $e->getCode());
			if($this->shouldRetryCall($e))
			{
				$this->getLogger()->info('Retrying soap call.');
				return $this->loadAvatar($a_vuserid);
			}

			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Loading avatar failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}
	
	/**
	 * Store avatar
	 * @param type $a_vuser_id
	 */
	public function storeAvatarUsingBase64($a_vuser_id, $a_file_info = array())
	{
		try {
			$GLOBALS['ilLog']->write(__METHOD__.': Starting update of avatar image...');
			$this->initClient();
			
			$avatar = new stdClass();
			$avatar->userid = $a_vuser_id;
			$avatar->filename = $a_file_info['name'];
			$avatar->type = $a_file_info['type'];
			$avatar->file = base64_encode(file_get_contents($a_file_info['file']));
			
			return $this->getClient()->storeAvatarUsingBase64String($avatar);
		}
		catch(SoapFault $e)
		{
			$this->getLogger()->warning('Calling webservice failed with message: ' . $e->getMessage().' with code: ' . $e->getCode());
			if($this->shouldRetryCall($e))
			{
				$this->getLogger()->info('Retrying soap call.');
				return $this->storeAvatarUsingBase64($a_vuser_id, $a_file_info);
			}
			
			$code = $this->parseErrorCode($e);
			$GLOBALS['ilLog']->write(__METHOD__.': Store avatar failed with message code: '.$code);
			$GLOBALS['ilLog']->write(__METHOD__.': Last request: '.$this->getClient()->__getLastRequest());
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}


	/**
	 * Load user data from ilias user object
	 * @param stdclass $user
	 * @param ilObjUser $iu
	 */
	private function loadFromUser($user, ilObjUser $iu)
	{
		$prefix = ilViteroSettings::getInstance()->getUserPrefix();
		$user->username = $prefix.$iu->getLogin();
		$user->surname = $iu->getLastname();
		$user->firstname = $iu->getFirstname();
		$user->email = $iu->getEmail();
		$user->company = $iu->getInstitution();

		$user->locale = in_array($iu->getLanguage(), $this->available_locales)
			? $iu->getLanguage()
			: "en";

		#$user->timezone = trim($iu->getTimeZone());
		$GLOBALS['ilLog']->write(__METHOD__.': Time zone is '. $iu->getTimeZone());


		$user->phone = $iu->getPhoneOffice();
		$user->fax = $iu->getFax();
		$user->mobile = $iu->getPhoneMobile();
		$user->country = $iu->getCountry();
		$user->zip = $iu->getZipcode();
		$user->city = $iu->getCity();
		$user->street = $iu->getStreet();
	}


	protected function getWsdlName()
	{
		return self::WSDL_NAME;
	}


}
?>
