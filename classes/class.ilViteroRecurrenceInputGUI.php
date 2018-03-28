<?php
/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/


include_once('./Services/Calendar/classes/class.ilCalendarUserSettings.php');
include_once './Services/Calendar/classes/class.ilCalendarRecurrence.php';

/**
* This class represents an input GUI for recurring events/appointments (course events or calendar appointments) 
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ServicesCalendar
*/

class ilViteroRecurrenceInputGUI extends ilCustomInputGUI
{
	private $freq_type = 0;
	private $freq_end = null;

	private $rec = 0;

	public function  __construct($a_title, $a_postvar)
	{
		global $lng,$tpl,$ilUser;

		$this->lng = $lng;
		$this->lng->loadLanguageModule('dateplaner');

		$this->user_settings = ilCalendarUserSettings::_getInstanceByUserId($ilUser->getId());
		$tpl->addJavascript("./Services/Calendar/js/recurrence_input.js");

		parent::__construct($a_title,$a_postvar);
	}

	public function getFrequenceType()
	{
		return $this->freq_type;
	}

	public function getFrequenceUntilDate()
	{
		return $this->freq_end;
	}

	public function setFrequenceUntilDate(ilDate $dt)
	{
		$this->freq_end = $dt;
	}

	public function setReccurrence($a_rec)
	{
		$this->rec = $a_rec;
	}

	public function getReccurrence()
	{
		return $this->rec;
	}

	/**
	 * check input
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function checkInput()
	{
		global $lng;

		$this->loadRecurrence();
		return true;
	}

	protected function loadRecurrence()
	{
		$this->freq_type = (int) $_POST['frequence'];

		switch($_POST['until_type'])
		{
			case 0:
				break;

			case 3:
				$dtig = new ilDateTimeInputGUI('','until_end');
				$dtig->setRequired(true);
				if($dtig->checkInput())
				{
					$this->setFrequenceUntilDate($dtig->getDate());
				}
				break;
		}
	}



	/**
	 * insert
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function insert($a_tpl)
	{
		$tpl = ilViteroPlugin::getInstance()->getTemplate('tpl.recurrence_input.html',true,true);

		$options = array(
			ilViteroUtils::REC_ONCE => ilViteroUtils::recurrenceToString(ilViteroUtils::REC_ONCE),
			ilViteroUtils::REC_DAILY => ilViteroUtils::recurrenceToString(ilViteroUtils::REC_DAILY),
			ilViteroUtils::REC_WEEKLY => ilViteroUtils::recurrenceToString(ilViteroUtils::REC_WEEKLY),
			ilViteroUtils::REC_WEEKDAYS => ilViteroUtils::recurrenceToString(ilViteroUtils::REC_WEEKDAYS),
			ilViteroUtils::REC_WEEKENDS => ilViteroUtils::recurrenceToString(ilViteroUtils::REC_WEEKENDS)
		);

		$tpl->setVariable('FREQUENCE',ilUtil::formSelect(
			$this->getReccurrence(),
			'frequence',
			$options,
			false,
			true,
			'',
			'',
			array('onchange' => 'ilHideFrequencies();','id' => 'il_recurrence_1')));

		$tpl->setVariable('TXT_EVERY',$this->lng->txt('cal_every'));

		// UNTIL
		$this->buildUntilSelection($tpl);

		$a_tpl->setCurrentBlock("prop_custom");
		$a_tpl->setVariable("CUSTOM_CONTENT", $tpl->get());
		$a_tpl->parseCurrentBlock();
	}

	/**
	 * build selection for ending date
	 *
	 * @access protected
	 * @param object tpl
	 * @return
	 */
	protected function buildUntilSelection($tpl)
	{

		$tpl->setVariable('TXT_NO_ENDING',$this->lng->txt('cal_no_ending'));

		$tpl->setVariable('TXT_UNTIL_CREATE',$this->lng->txt('cal_create'));
		$tpl->setVariable('TXT_APPOINTMENTS',$this->lng->txt('cal_appointments'));

		if($this->freq_end instanceof ilDate)
		{
			$tpl->setVariable('UNTIL_END_CHECKED','checked="checked"');
		}
		else
		{
			$tpl->setVariable('UNTIL_NO_CHECKED','checked="checked"');
		}

		$tpl->setVariable('TXT_UNTIL_END',$this->lng->txt('cal_repeat_until'));
		$dt = new ilDateTimeInputGUI('','until_end');
		$dt->setDate(
			$this->freq_end instanceof ilDate ? $this->freq_end : new ilDate(time(),IL_CAL_UNIX)
		);
		$tpl->setVariable('UNTIL_END_DATE',$dt->getTableFilterHTML());
	}


}
?>
