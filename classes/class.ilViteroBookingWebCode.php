<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilViteroBookingWebCode
{
	private $vgroup_id = 0;
	private $booking_id = 0;
	private $webcode = '';
	private $browserurl = '';
	private $appurl = '';
	
	private $exists = false;
	
	/**
	 * @var ilDBInterface
	 */
	private $db;
	
	public function __construct($vgroup_id = 0, $booking_id = 0)
	{
		global $DIC;
		
		$this->db = $DIC->database();
		
		$this->vgroup_id = $vgroup_id;
		$this->booking_id = $booking_id;
		$this->read();
	}

	/**
	 * @param $a_vgroup
	 */
	public static function deleteByVgroup($a_vgroup)
	{
		global $DIC;
		
		$db = $DIC->database();
		
		$query = 'DELETE from rep_robj_xvit_webcodes '.
			'WHERE vgroup_id = '.$db->quote($a_vgroup,'integer');
		$db->manipulate($query);
	}
	
	public function exists()
	{
		return $this->exists;
	}
	
	/**
	 * get booking id
	 * @return int
	 */
	public function getBookingId()
	{
		return $this->booking_id;
	}
	
	public function setWebCode($a_code)
	{
		$this->webcode = $a_code;
	}
	
	public function getWebCode()
	{
		return $this->webcode;
	}
	
	public function getVgroupId()
	{
		return $this->vgroup_id;
	}
	
	public function save()
	{
		if($this->exists())
		{
			return $this->update();
		}
		return $this->insert();
	}
	
	public function delete()
	{
		$query = 'DELETE from rep_robj_xvit_webcodes '.
			'WHERE booking_id = '.$this->db->quote($this->getBookingId(), 'integer').' '.
			'AND vgroup_id = '.$this->db->quote($this->getVgroupId(), 'integer');
		$this->db->manipulate($query);
	}

	/**
	 *
	 */
	public function setBrowserUrl($a_url)
	{
		$this->browserurl = $a_url;
	}

	public function getBrowserUrl()
	{
		return $this->browserurl;
	}

	public function setAppUrl($a_url)
	{
		$this->appurl = $a_url;
	}

	public function getAppUrl()
	{
		return $this->appurl;
	}

	public function update()
	{
		$query = 'UPDATE rep_robj_xvit_codes '.
			'SET webcode = '.$this->db->quote($this->getWebCode(), 'text').', '.
			'browserurl = '.$this->db->quote($this->getWebUrl(),'text').', '.
			'appurl = '.$this->db->quote($this->getAppUrl(),'text').' '.
			'WHERE booking_id = ' . $this->db->quote($this->getBookingId(), 'integer').' '.
			'AND vgroup_id = '.$this->db->quote($this->getVgroupId(), 'integer');
		$this->db->manipulate($query);
	}
	
	public function insert()
	{
		$query = 'INSERT INTO rep_robj_xvit_webcodes (vgroup_id, booking_id, webcode, browserurl, appurl) '.
			'VALUES( '.
			$this->db->quote($this->getVgroupId(), 'integer').', '.
			$this->db->quote($this->getBookingId(), 'integer').', '.
			$this->db->quote($this->getWebCode(), 'text').', '.
			$this->db->quote($this->getBrowserUrl(), 'text').', '.
			$this->db->quote($this->getAppUrl(), 'text').' '.
			')';
		$this->db->manipulate($query);
	}
	
	private function read()
	{
		$query = 'SELECT * FROM rep_robj_xvit_webcodes '.
			'WHERE vgroup_id = '.$this->db->quote($this->getVgroupId(), 'integer').' '.
			'AND booking_id = '. $this->db->quote($this->getBookingId(), 'integer');
		$res = $this->db->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$this->exists = true;
			$this->webcode = $row->webode;
			$this->browserurl = $row->browserurl;
			$this->appurl = $row->appurl;
		}
	}
}

?>