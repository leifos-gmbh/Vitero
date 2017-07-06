<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Abstract vitero soap connector
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroSoapConnector.php 33586 2012-03-07 13:12:56Z smeyer $
 */
abstract class ilViteroSoapConnector
{
	const ERR_WSDL = 2001;
	
	const MAX_WSDL_RETRIES = 5;
	const MAX_RETRIES = 3;
	const CONNECTION_TIMEOUT = 3;

	const WS_TIMEZONE = 'Africa/Ceuta';
	const CONVERT_TIMZONE = 'Africa/Ceuta';
	const CONVERT_TIMEZONE_FIX = 'Africa/Ceuta';

	private $settings;
	private $plugin;

	private $client = null;
	
	/**
	 * @var ilLogger
	 */
	private $logger = null;
	
	private $wsdl_retry_counter = 0;
	private $retry_counter = 0;
	private $client_initialized = false;
	

	/**
	 * Get instance
	 */
	public function __construct()
	{
		$this->plugin = ilViteroPlugin::getInstance();
		$this->settings = ilViteroSettings::getInstance();
		$this->logger = ilLoggerFactory::getLogger('xvit');
	}

	/**
	 * Get wsdl name
	 * @return string
	 */
	abstract protected function getWsdlName();
	
	/**
	 * Get logger
	 * @return ilLogger
	 */
	protected function getLogger()
	{
		return $this->logger;
	}

	/**
	 *
	 * @return <type>
	 */
	public function getPluginObject()
	{
		return $this->plugin;
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
	 * Get soap client
	 * @return SoapClient
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * init soap client
	 * @return void
	 * @throws ilViteroConnectorException
	 */
	protected function initClient()
	{
		if($this->client_initialized)
		{
			// new initialization is required for different calls of same group
			#return true;
		}
		
		try {
			$this->logger->debug('Using wsdl: ' . $this->getSettings()->getServerUrl().'/'.$this->getWsdlName());
			$this->client = new SoapClient(
				$this->getSettings()->getServerUrl().'/'.$this->getWsdlName(),
				array(
					'cache_wsdl' => 0,
					'trace' => 1,
					'exceptions' => true,
					'classmap',
					'connection_timeout' => self::CONNECTION_TIMEOUT
				)
			);
			$this->client->__setSoapHeaders(
				$head = new ilViteroSoapWsseAuthHeader(
					$this->getSettings()->getAdminUser(),
					$this->getSettings()->getAdminPass()
				)
			);
			$this->client_initialized = true;
			return;
		}
		catch(SoapFault $e) {

			$this->logger->notice('Caught exception: ' . $e->getCode().' '.$e->getMessage());

			if(stristr($e->getMessage(), 'Parsing WSDL: Couldn\'t load from'))
			{
				if(++$this->wsdl_retry_counter <= self::MAX_RETRIES) {
					$this->logger->warning('Retry connection...');
					sleep(2);
					return $this->initClient();
				}
			}
			$this->logger->warning('Loading wsdl failed with message: ' . $e->getMessage());
			throw new ilViteroConnectorException('',self::ERR_WSDL);
		}
	}

	protected function parseErrorCode(Exception $e)
	{
		return (int) $e->detail->error->errorCode;
	}
	
	/**
	 * Check if a retry of the soap call should be performed, due to soap connection timeout. 
	 * @param SoapFault $e
	 */
	protected function shouldRetryCall(SoapFault $e)
	{
		if(!stristr($e->getMessage(), 'Could not connect to host'))
		{
			$this->getLogger()->debug('Caught error: ' . $e->getMessage());
			return false;
		}
		if(++$this->retry_counter <= self::MAX_RETRIES)
		{
			sleep(5);
			return true;
		}
	}

}

?>
