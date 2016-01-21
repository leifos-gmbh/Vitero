<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * 
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroGroupSoap.php 31001 2011-10-05 12:57:25Z smeyer $
 */
class ilViteroGroupSoap
{
	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	public function initCustomer()
	{
		$this->customerid = (int) ilViteroSettings::getInstance()->getCustomer();
	}

	public function setName($a_name)
	{
		$this->groupname = (string) $a_name;
	}

	public function setGroupId($a_id)
	{
		$this->groupid = (int) $a_id;
	}

}
?>