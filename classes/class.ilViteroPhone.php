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
	private $dialoutphoneparticipant = true;


	/**
	 * Init from property form input
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	public function initFromForm(ilPropertyFormGUI $form)
	{
		$phone_group = $form->getItemByPostVar('phone_options');
		if(!$phone_group instanceof ilCheckboxGroupInputGUI) {
			return false;
		}
		$options = $form->getInput('phone_options');
		$this->phoneconference = in_array(ilViteroSettings::PHONE_CONFERENCE, $options);
		$this->dialout = in_array(ilViteroSettings::PHONE_DIAL_OUT, $options);
		$this->dialoutphoneparticipant = in_array(ilViteroSettings::PHONE_DIAL_OUT_PART,$options);
	}


}