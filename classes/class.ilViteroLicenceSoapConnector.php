<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * 
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroLicenceSoapConnector.php 32068 2011-12-13 11:36:55Z smeyer $
 */
class ilViteroLicenceSoapConnector extends ilViteroSoapConnector
{
	const WSDL_NAME = 'licence.wsdl';

	/**
	 * Create Group
	 * @param ilViteroGroupSoap $group
	 * @throws ilViteroConnectorException $e
	 */
	public function getModulesForCustomer($a_cust_id)
	{
		try {
			
			$this->initClient();

			// Wrap into single group object
			$lic = new stdClass();
			$lic->customerid = $a_cust_id;

			$modules = $this->getClient()->getModulesForCustomer($lic);

			return $modules;
		}
		catch(SoapFault $e)
		{
			$this->getLogger()->warning('Calling webservice failed with message: ' . $e->getMessage().' with code: ' . $e->getCode());
			if($this->shouldRetryCall($e))
			{
				$this->getLogger()->info('Retrying soap call.');
				return $this->getModulesForCustomer($a_cust_id);
			}
			
			$code = $this->parseErrorCode($e);
			throw new ilViteroConnectorException($e->getMessage(),$code);
		}
	}


	protected function getWsdlName()
	{
		return self::WSDL_NAME;
	}
}
?>