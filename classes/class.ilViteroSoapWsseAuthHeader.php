<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Generates an WSSE header
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroSoapWsseAuthHeader.php 32250 2011-12-21 11:43:49Z smeyer $
 */
class ilViteroSoapWsseAuthHeader extends SoapHeader
{
	const WSS_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
	const WSU_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';

	public function __construct($a_user,$a_pass)
	{

		$auth = new stdClass();
		$auth->Username = new SoapVar(
			$a_user,
			XSD_STRING,
			NULL,
			self::WSS_NS,
			NULL,
			self::WSS_NS
		);
		$auth->Password = new SoapVar(
			$a_pass,
			XSD_STRING,
			NULL,
			self::WSS_NS,
			NULL,
			self::WSS_NS
		);


		$auth->Nonce = new SoapVar(
			base64_encode(sha1($a_pass.$a_user.microtime(true))),
			XSD_STRING,
			NULL,
			self::WSS_NS,
			NULL,
			self::WSS_NS
		);

		$current_date = new ilDateTime(time(),IL_CAL_UNIX);
		$current_time = $current_date->get(IL_CAL_FKT_DATE,'Y-m-d\TH:i:s\Z',  ilTimeZone::UTC);
		$auth->Created = new SoapVar(
			$current_time,
			XSD_STRING,
			NULL,
			self::WSU_NS,
			NULL,
			self::WSU_NS
		);
		$un_token = new stdClass();
		$un_token->UsernameToken = new SoapVar(
			$auth,
			SOAP_ENC_OBJECT,
			NULL,
			self::WSS_NS,
			'UsernameToken',
			self::WSS_NS
		);

		$security = new SoapVar(
			new SoapVar(
				$un_token,
				SOAP_ENC_OBJECT,
				NULL,
				self::WSS_NS,
				'UsernameToken',
				self::WSS_NS
			),
			SOAP_ENC_OBJECT,
			NULL,
			self::WSS_NS,
			'Security',
			self::WSS_NS
		);
		parent::__construct(self::WSS_NS, 'Security', $security, TRUE);
	}

	public static function getWSFHeader($user, $pass)
	{
		$current_date = new ilDateTime(time(),IL_CAL_UNIX);
		$sec = new WSHeader(
			array(
				'name' => 'Security',
				'ns'	=> self::WSS_NS,
				'prefix' => 'wsse',
				'mustunderstand' => true,
				'data' => array(
					new WSHeader(
						array(
							'name' => 'UsernameToken',
							'ns' => self::WSS_NS,
							'prefix' => 'wsse',
							'data' => array(
								new WSHeader(
									array(
										'name' => 'Username',
										'ns' => self::WSS_NS,
										'prefix' => 'wsse',
										'data' => $user
									)
								),
								new WSHeader(
									array(
										'name' => 'Password',
										'ns' => self::WSS_NS,
										'prefix' => 'wsse',
										'data' => $pass
									)
								),
								new WSHeader(
									array(
										'name' => 'Nonce',
										'ns' => self::WSS_NS,
										'prefix' => 'wsse',
										'data' => base64_encode(sha1($a_pass.$a_user.microtime(true)))
									)
								),
								new WSHeader(
									array(
										'name' => 'Created',
										'ns' => self::WSU_NS,
										'prefix' => 'wsu',
										'data' => $current_date->get(IL_CAL_FKT_DATE,'Y-m-d\TH:i:s\Z',  ilTimeZone::UTC)
									)
								)
							)
						)
					)
				)
			)
		);
		return $sec;

	}

}
?>