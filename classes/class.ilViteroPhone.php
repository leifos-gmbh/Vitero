<?php
/**
 * vitero phone
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroSettings.php 36242 2012-08-15 13:00:21Z smeyer $
 */

class ilViteroPhone
{
	private $phoneconference = false;
	private $dialout = false;
	private $dialoutphoneparticipant = false;


	/**
	 * Init from property form input
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	public function initFromForm(ilPropertyFormGUI $form)
	{
		$settings = ilViteroSettings::getInstance();
		if($settings->isPhoneConferenceEnabled())
		{
			$this->phoneconference = (bool) $form->getInput('phone_conference');
		}
		if($settings->isPhoneDialOutEnabled())
		{
			$this->dialout = (bool) $form->getInput('phone_dial_out');
		}

		if($settings->isPhoneDialOutParticipantsEnabled())
		{
			$this->dialoutphoneparticipant = (bool) $form->getInput('phone_dial_out_part');
		}
	}

	public function isConferenceEnabled()
	{
		return $this->phoneconference;
	}

	public function isDialoutEnabled()
	{
		return $this->dialout;
	}

	public function isDialoutParticipantEnabled()
	{
		return $this->dialoutphoneparticipant;
	}


}