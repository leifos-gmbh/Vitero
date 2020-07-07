<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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


include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

/**
* User Interface class for example repository object.
*
* User interface classes process GET and POST parameter and call
* application classes to fulfill certain tasks.
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
*
* $Id: class.ilObjViteroGUI.php 56608 2014-12-19 10:11:57Z fwolf $
*
* Integration into control structure:
* - The GUI class is called by ilRepositoryGUI
* - GUI classes used by this class are ilPermissionGUI (provides the rbac
*   screens) and ilInfoScreenGUI (handles the info screen).
*
* @ilCtrl_isCalledBy ilObjViteroGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjViteroGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilRepositorySearchGUI
* @ilCtrl_Calls ilObjViteroGUI: ilCommonActionDispatcherGUI, ilLearningProgressGUI
*
*/
class ilObjViteroGUI extends ilObjectPluginGUI
{
	/**
	 * @var ilLogger
	 */
	private $vitero_logger = null;

	/**
	* Initialisation
	*/
	protected function afterConstructor()
	{
		$this->vitero_logger = $GLOBALS['DIC']->logger()->xvit();

		if($this->object) {
			$this->object->readLearningProgressSettings();
		}

		// anything needed after object has been constructed
		// - example: append my_id GET parameter to each request
		//   $ilCtrl->saveParameter($this, array("my_id"));
	}
	
	/**
	 * @return ilLogger
	 */
	protected function getLogger()
	{
		return $this->vitero_logger;
	}
	
	/**
	* Handles all commmands of this class, centralizes permission checks
	*/
	public function performCommand($cmd)
	{
		$next_class = $this->ctrl->getNextClass($this);
		switch($next_class)
		{
			case 'ilrepositorysearchgui':
				include_once('./Services/Search/classes/class.ilRepositorySearchGUI.php');
				$rep_search = new ilRepositorySearchGUI();
				$rep_search->setCallback($this,
					'addParticipants',
					array(
						ilObjVitero::MEMBER =>  ilViteroPlugin::getInstance()->txt('add_as_group_member'),
						ilObjVitero::ADMIN => ilViteroPlugin::getInstance()->txt('add_as_group_admin')
					));

				// Set tabs
				$this->tabs_gui->setTabActive('participants');
				$this->ctrl->setReturn($this,'participants');
				$ret = $this->ctrl->forwardCommand($rep_search);
				break;
		}

		switch ($cmd)
		{
			case "editProperties":		// list all commands that need write permission here
			case "updateProperties":
			case 'initViteroGroup':
			case 'participants':
			case 'confirmDeleteParticipants':
			case 'deleteParticipants':
			case 'sendMailToSelectedUsers':
			case 'confirmDeleteAppointment':
			case 'confirmDeleteAppointmentInSeries':
			case 'deleteBookingInSeries':
			case 'deleteBooking':
			case 'confirmDeleteBooking':
			case 'showAppointmentCreation':
			case 'createAppointment':
			case 'editBooking':
			case 'updateBooking':
			case 'unlockUsers':
			case 'lockUsers':
			case 'materials':
            case 'showMaterials':
			case 'startAdminSession':
			case 'syncLearningProgress':
			//case "...":
				$this->checkPermission("write");
				$this->$cmd();
				break;

			case "showContent":			// list all commands that need read permission here
			case 'startSession':
			//case "...":
			//case "...":
				$this->checkPermission("read");
				$this->$cmd();
				break;
		}
	}

	/**
	* Get type.
	*/
	final function getType()
	{
		return "xvit";
	}

	/**
	 * Init creation froms
	 *
	 * this will create the default creation forms: new
	 *
	 * @param	string	$a_new_type
	 * @return	array
	 */
	protected function initCreationForms($a_new_type)
	{
		$forms = array(
			self::CFORM_NEW => $this->initCreateForm($a_new_type),
			self::CFORM_CLONE => $this->fillCloneTemplate(null, $a_new_type)
			);

		return $forms;
	}

	/**
	 * init create form
	 * @param  $a_new_type
	 */
	public function  initCreateForm($a_new_type)
	{
		// @todo: handle this in delete event
		ilObjVitero::handleDeletedGroups();

		$form = parent::initCreateForm($a_new_type);
		$settings = ilViteroSettings::getInstance();

		// show selection
		if($settings->isCafeEnabled() and $settings->isStandardRoomEnabled())
		{
			$type_select = new ilRadioGroupInputGUI(
				ilViteroPlugin::getInstance()->txt('app_type'),
				'atype'
			);
			$type_select->setValue(ilViteroRoom::TYPE_CAFE);

			// Cafe
			$cafe = new ilRadioOption(
				ilViteroPlugin::getInstance()->txt('app_type_cafe'),
				ilViteroRoom::TYPE_CAFE
			);
			$type_select->addOption($cafe);

			$this->initFormCafe($cafe);

			// Standard
			$std = new ilRadioOption(
				ilViteroPlugin::getInstance()->txt('app_type_standard'),
				ilViteroRoom::TYPE_STD
			);
			$type_select->addOption($std);

			$this->initFormStandardRoom($std);


			$form->addItem($type_select);
		}
		elseif($settings->isCafeEnabled())
		{
			$this->initFormCafe($form);
		}
		elseif($settings->isStandardRoomEnabled())
		{
			$this->initFormStandardRoom($form);
		}

		$this->initFormTimeBuffer($form);
		$this->initFormPhone($form);
		$this->initFormRecorder($form);
		$this->initFormMobileAccess($form);
		$this->initFormAnonymousAccess($form);
		$this->initFormRoomSize($form);


		return $form;
	}

	/**
	 * @param ilPropertyFormGUI $form
	 */
	protected function initFormMobileAccess(ilPropertyFormGUI $form, $a_booking_id = 0)
	{
		if(!ilViteroSettings::getInstance()->isMobileAccessEnabled())
		{
			return false;
		}

		$mobile = new ilCheckboxInputGUI(
			$this->getPlugin()->txt('form_mobile'),
			'mobile'
		);
		$mobile->setInfo($this->getPlugin()->txt('form_mobile_info'));
		$mobile->setValue(1);

		if($this->object instanceof ilObjVitero)
		{
			$web_access = new ilViteroBookingWebCode($this->object->getVGroupId(),$a_booking_id);
			if($web_access->exists())
			{
				$mobile->setChecked(true);
			}
		}
		$form->addItem($mobile);
	}

	/**
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	protected function initFormRecorder(ilPropertyFormGUI $form)
	{
		if(!ilViteroSettings::getInstance()->isSessionRecorderEnabled())
		{
			return false;
		}

		$recorder = new ilCheckboxInputGUI(
			$this->getPlugin()->txt('form_recorder'),
			'recorder'
		);
		$recorder->setInfo(
			$this->getPlugin()->txt('form_recorder_info')
		);
		$recorder->setValue(1);
		$form->addItem($recorder);
		return true;
	}
	
	/**
	 * Init checkbox for anonymous access
	 * @param ilPropertyFormGUI $form
	 */
	protected function initFormAnonymousAccess(ilPropertyFormGUI $form, $a_booking_id = 0)
	{
		$anon = new ilCheckboxInputGUI(
			ilViteroPlugin::getInstance()->txt('form_anonymous_access'),
			'anonymous_access'
		);
		$anon->setValue(1);
		$anon->setInfo(
			ilViteroPlugin::getInstance()->txt('form_anonymous_access_info')
		);
		if($a_booking_id)
		{
			$booking_info = new ilViteroBookingCode(
				$this->object->getVGroupId(),
				$a_booking_id
			);
			$anon->setChecked($booking_info->exists());
		}
		$form->addItem($anon);
		return $form;
	}

	protected function initFormCafe($parent,$a_create = true)
	{
		global $lng, $tpl;

		$lng->loadLanguageModule('dateplaner');
		$lng->loadLanguageModule('crs');

		$tpl->addJavaScript('./Services/Form/js/date_duration.js');
		include_once './Services/Form/classes/class.ilDateDurationInputGUI.php';
		$dur = new ilDateDurationInputGUI($lng->txt('cal_fullday'),'cafe_time');
		if(!$a_create)
		{
			$dur->setDisabled(true);
		}
		
		$dur->setStartText($this->getPlugin()->txt('event_start_date'));
		$dur->setEndText($this->getPlugin()->txt('event_end_date'));
		$dur->setShowTime(false);

		$start = new ilDate(time(),IL_CAL_UNIX);
		$end = clone $start;
		$end->increment(IL_CAL_MONTH,1);

		$dur->setStart($start);
		$dur->setEnd($end);

		if($parent instanceof ilPropertyFormGUI)
		{
			$parent->addItem($dur);
		}
		else
		{
			$parent->addSubItem($dur);
		}
	}

	public function initFormStandardRoom($parent,$a_create = true)
	{
		global $lng, $tpl;

		$lng->loadLanguageModule('dateplaner');
		$lng->loadLanguageModule('crs');

		$tpl->addJavaScript('./Services/Form/js/date_duration.js');
		include_once './Services/Form/classes/class.ilDateDurationInputGUI.php';
		$dur = new ilDateDurationInputGUI($lng->txt('cal_fullday'),'std_time');
		$dur->setMinuteStepSize(15);
		$dur->setStartText($this->getPlugin()->txt('event_start_date'));
		$dur->setEndText($this->getPlugin()->txt('event_end_date'));
		$dur->setShowTime(true);

		$start = new ilDate(time(),IL_CAL_UNIX);
		$end = clone $start;

		$dur->setStart($start);
		$dur->setEnd($end);

		if($parent instanceof ilPropertyFormGUI)
		{
			$parent->addItem($dur);
		}
		else
		{
			$parent->addSubItem($dur);
		}

		if($a_create)
		{
			$lng->loadLanguageModule('dateplaner');
			$rec = new ilViteroRecurrenceInputGUI($lng->txt('cal_recurrences'), 'rec');
			if($parent instanceof ilPropertyFormGUI)
			{
				$parent->addItem($rec);
			}
			else
			{
				$parent->addSubItem($rec);
			}
		}
	}

	/**
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	protected function initFormPhone(ilPropertyFormGUI $form)
	{
		$settings = ilViteroSettings::getInstance();

		// conference
		if($settings->isPhoneConferenceEnabled())
		{
			$conference = new ilCheckboxInputGUI(
				ilViteroPlugin::getInstance()->txt('form_phone_conference'),
				'phone_conference'
			);
			$conference->setInfo(
				ilViteroPlugin::getInstance()->txt('form_phone_conference_info')
			);
			$conference->setValue(1);
			$form->addItem($conference);
		}

		// dial out
		if($settings->isPhoneDialOutEnabled())
		{
			$dial_out = new ilCheckboxInputGUI(
				ilViteroPlugin::getInstance()->txt('form_phone_dial_out'),
				'phone_dial_out'
			);
			$dial_out->setValue(1);
			$dial_out->setInfo(
				ilViteroPlugin::getInstance()->txt('form_phone_dial_out_info')
			);
			$form->addItem($dial_out);
		}

		if($settings->isPhoneDialOutParticipantsEnabled())
		{
			$dial_out_phone_part = new ilCheckboxInputGUI(
				ilViteroPlugin::getInstance()->txt('form_phone_dial_out_part'),
				'phone_dial_out_part'
			);
			$dial_out_phone_part->setValue(1);
			$dial_out_phone_part->setInfo(
				ilViteroPlugin::getInstance()->txt('form_phone_dial_out_part_info')
			);
			$form->addItem($dial_out_phone_part);
		}
	}

	/**
	 * Init time buffer settings
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	protected function initFormTimeBuffer(ilPropertyFormGUI $form)
	{
		$tbuffer = new ilNonEditableValueGUI(
			ilViteroPlugin::getInstance()->txt('time_buffer'),
			'dummy'
		);

		// Buffer before
		$buffer_before = new ilSelectInputGUI(
			ilViteroPlugin::getInstance()->txt('time_buffer_before'),
			'buffer_before'
		);
		$buffer_before->setOptions(
			array(
				0 => '0 min',
				15 => '15 min',
				30 => '30 min',
				45 => '45 min',
				60 => '1 h'
			)
		);

		$buffer_before->setValue(ilViteroSettings::getInstance()->getStandardGracePeriodBefore());
		$tbuffer->addSubItem($buffer_before);

		// Buffer after
		$buffer_after = new ilSelectInputGUI(
			ilViteroPlugin::getInstance()->txt('time_buffer_after'),
			'buffer_after'
		);
		$buffer_after->setOptions(
			array(
				0 => '0 min',
				15 => '15 min',
				30 => '30 min',
				45 => '45 min',
				60 => '1 h'
			)
		);
		$buffer_after->setValue(ilViteroSettings::getInstance()->getStandardGracePeriodAfter());
		$tbuffer->addSubItem($buffer_after);

		$form->addItem($tbuffer);
		return true;
	}

	/**
	 * Ini form for room size
	 * @param ilPropertyFormGUI $form
	 * @return bool
	 */
	protected function initFormRoomSize(ilPropertyFormGUI $form,$a_create = true)
	{
		$room_size_list = ilViteroUtils::getRoomSizeList();

		if(!count($room_size_list))
		{
			return false;
		}
		
		$room_size = new ilSelectInputGUI(ilViteroPlugin::getInstance()->txt('room_size'), 'room_size');
		$room_size->setOptions($room_size_list);

		if(!$a_create)
		{
			$room_size->setDisabled(true);
		}

		$form->addItem($room_size);
		return true;
	}

	protected function loadCafeSettings($form,$room)
	{
		$room->enableCafe(true);
		$GLOBALS['ilLog']->write(__METHOD__.': '.$form->getItemByPostVar('cafe_time')->getStart());
		$room->setStart($form->getItemByPostVar('cafe_time')->getStart());

		$end = clone $form->getItemByPostVar('cafe_time')->getStart();
		$end->increment(ilDateTime::DAY,1);
		$room->setEnd($end);
		$room->setRepetitionEndDate($form->getItemByPostVar('cafe_time')->getEnd());
		return $room;
	}

	protected function loadStandardRoomSettings($form,$room)
	{
		$room->enableCafe(false);
		$room->setStart($form->getItemByPostVar('std_time')->getStart());
		$room->setEnd($form->getItemByPostVar('std_time')->getEnd());
		$room->setBufferBefore($form->getInput('buffer_before'));
		$room->setBufferAfter($form->getInput('buffer_after'));

		if($form->getItemByPostVar('rec'))
		{
			$room->setRepetition($form->getItemByPostVar('rec')->getFrequenceType());
			$room->setRepetitionEndDate($form->getItemByPostVar('rec')->getFrequenceUntilDate());
		}

		return $room;
	}

	/**
	 *
	 * @global <type> $ilCtrl
	 * @global <type> $ilUser
	 * @param ilObjVitero $newObj
	 */
	public function afterSave(ilObject $newObj)
	{
		global $ilCtrl, $ilUser;

		$settings = ilViteroSettings::getInstance();
		$form = $this->initCreateForm('xvit');
		$form->checkInput();

		//$phone_enabled = $form->getInput('')

		$room = new ilViteroRoom();
		$room->setRoomSize($form->getInput('room_size'));
		$room->enableRecorder($form->getInput('recorder'));

		$phone = new ilViteroPhone();
		$phone->initFromForm($form);
		$room->setPhone($phone);



		if($settings->isCafeEnabled() and $settings->isStandardRoomEnabled())
		{
			if($form->getInput('atype') == ilViteroRoom::TYPE_CAFE)
			{
				$room = $this->loadCafeSettings($form, $room);
			}
			else
			{
				$room = $this->loadStandardRoomSettings($form, $room);
			}

			$room->isCafe($form->getInput('atype') == ilViteroRoom::TYPE_CAFE);
		}
		elseif($settings->isCafeEnabled())
		{
			$this->loadCafeSettings($form, $room);
		}
		else
		{
			$this->loadStandardRoomSettings($form, $room);
		}


		try {
			$newObj->initVitero($ilUser->getId());
			$newObj->initAppointment(
				$room,
				$form->getInput('anonymous_access'),
				$form->getInput('mobile')
			);
			ilUtil::sendSuccess(ilViteroPlugin::getInstance()->txt('created_vitero'), true);
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getViteroMessage(),true);
		}

		$newObj->addParticipants(array($ilUser->getId()), ilObjVitero::ADMIN);
		parent::afterSave($newObj);
	}

	/**
	* After object has been created -> jump to this command
	*/
	public function getAfterCreationCmd()
	{
		return "showContent";
	}

	/**
	* Get standard command
	*/
	public function getStandardCmd()
	{
		return "showContent";
	}
	
//
// DISPLAY TABS
//
	
	/**
	* Set tabs
	*/
	public function setTabs()
	{
		global $ilTabs, $ilCtrl, $ilAccess;
		

		// standard info screen tab
		$this->addInfoTab();

		// tab for the "show content" command
		
		
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("content", $this->txt("app_tab"), $ilCtrl->getLinkTarget($this, "showContent"));
		}

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$filesEnabled = ilViteroSettings::getInstance()->isContentAdministrationEnabled();
			if($filesEnabled)
			{
				$ilTabs->addTab('materials', $this->txt('materials'), $ilCtrl->getLinkTarget($this,'materials'));
			}
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		if($ilAccess->checkAccess('write','',$this->object->getRefId()))
		{
			$ilTabs->addTab(
				'participants',
				$this->txt('members'),
				$ilCtrl->getLinkTarget($this,'participants')
			);
		}

		include_once './Services/Tracking/classes/class.ilLearningProgressAccess.php';
		if(ilLearningProgressAccess::checkAccess($this->object->getRefId()) && $this->object->isLearningProgressActive())
		{
			$ilTabs->addTab(
				'learning_progress',
				$this->txt('learning_progress'),
				$ilCtrl->getLinkTargetByClass('illearningprogressgui',''));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}
	
	/**
	* Edit Properties. This commands uses the form class to display an input form.
	*/
	public function editProperties()
	{
		global $tpl, $ilTabs;

		// TODO: I keep the individual session sync button here while finding a solution via executeCommand
		if(ilLearningProgressAccess::checkAccess($this->object->getRefId()) && $this->object->isLearningProgressActive())
		{
			$this->addSyncLearningProgressButton();
		}

		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$tpl->setContent($this->form->getHTML());
	}

	/**
	 * Sync only 1 vitero group / ilias vitero session.
	 * @throws ilDateTimeException
	 */
	protected function syncLearningProgress()
	{
		$vitero_group_id = (int)$this->object->getVGroupId();

		$vitero_learning_progress = new ilViteroLearningProgress();
		$vitero_learning_progress->updateLearningProgress($vitero_group_id);

		ilUtil::sendInfo(ilViteroPlugin::getInstance()->txt('info_msg_session_sinc_success'),true);

		$this->editProperties();
	}

	protected function addSyncLearningProgressButton()
	{
		global $DIC;

		$ui_factory = $DIC->ui()->factory();
		$toolbar = $DIC->toolbar();

		$btn_refresh_lp = $ui_factory->button()->standard(
			ilViteroPlugin::getInstance()->txt('btn_sync_lp'),
			$this->ctrl->getLinkTarget($this,'syncLearningProgress')
		);

		$toolbar->addText(ilViteroPlugin::getInstance()->txt("btn_sync_lp_info"));

		$toolbar->addComponent($btn_refresh_lp);
	}
	
	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
		global $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
	
		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);
		
		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);
		if($this->object->isLearningProgressAvailable())
		{
			$this->addLearningProgressSettingsSection();
		}

		$this->form->addCommandButton("updateProperties", $this->txt("save"));

		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}

	protected function addLearningProgressSettingsSection()
	{
		$vitero_plugin = ilViteroPlugin::getInstance();

		$pres = new ilFormSectionHeaderGUI();
		$pres->setTitle($vitero_plugin->txt('edit_learning_progress_properties'));
		$this->form->addItem($pres);

		$num_appointments = $this->object->getNumberOfAppointmentsForSession();

		$learning_progress = new ilCheckboxInputGUI($vitero_plugin->txt('activate_learning_progress'), 'learning_progress');
		$learning_progress->setInfo($vitero_plugin->txt('activate_learning_progress_info'));

		$minimum_percentage = new ilNumberInputGUI($vitero_plugin->txt("min_percentage"),"min_percentage");
		$minimum_percentage->setInfo($vitero_plugin->txt("min_percentage_info"));
		$minimum_percentage->setMaxValue(100);
		$minimum_percentage->setMinValue(0);
		$minimum_percentage->setMaxLength(3);
		$minimum_percentage->setSize(3);
		$minimum_percentage->setSuffix("%");
		$minimum_percentage->setRequired(true);

		$learning_progress->addSubItem($minimum_percentage);

		$mode = new ilRadioGroupInputGUI($vitero_plugin->txt("mode"),"mode");
		$mode->setRequired(true);

		$one_session = new ilRadioOption($vitero_plugin->txt("one_session"),ilObjVitero::LP_MODE_ONE);
		if($num_appointments > 1) {
			$one_session->setDisabled(true);
			$one_session->setInfo($vitero_plugin->txt("currently_multi_appointments"));
		}
		$mode->addOption($one_session);

		$multi_session = new ilRadioOption($vitero_plugin->txt("multi_session"), ilObjVitero::LP_MODE_MULTI);
		$minimum_sessions = new ilNumberInputGUI($vitero_plugin->txt("min_sessions"), "min_sessions");
		$minimum_sessions->setInfo($vitero_plugin->txt("min_sessions_info"));
		$minimum_sessions->setMinValue(1);
		$minimum_sessions->setMaxLength(3);
		$minimum_sessions->setSize(3);
		$minimum_sessions->setRequired(true);
		$multi_session->addSubItem($minimum_sessions);

		if($num_appointments === 1){
			$multi_session->setDisabled(true);
			$multi_session->setInfo($vitero_plugin->txt("currently_one_appointment"));
		}

		$mode->addOption($multi_session);

		$learning_progress->addSubItem($mode);

		$this->form->addItem($learning_progress);

	}
	
	/**
	* Get values for edit properties form
	*/
	public function getPropertiesValues()
	{
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values['learning_progress'] = $this->object->isLearningProgressActive();
		$values['min_percentage'] = $this->object->getLearningProgressMinPercentage();
		$values['mode'] = $this->object->isLearningProgressModeMultiActive();
		$values['min_sessions'] = $this->object->getLearningProgressMinSessions();

		$this->form->setValuesByArray($values);
	}
	
	/**
	* Update properties
	*/
	public function updateProperties()
	{
		global $tpl, $lng, $ilCtrl, $ilTabs;
	
		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));

			$this->object->setLearningProgress($this->form->getInput("learning_progress"));
			$this->object->setLearningProgressMinPercentage($this->form->getInput("min_percentage"));
			$this->object->setLearningProgressModeMulti($this->form->getInput("mode"));
			$this->object->setLearningProgressMinSessions($this->form->getInput("min_sessions"));

			$this->object->saveLearningProgressData();
			$this->object->update();

			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$ilTabs->activateTab("properties");

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}

//
// Show content
//

	/**
	* Show content
	*/
	public function showContent()
	{
		global $tpl, $ilTabs, $ilAccess, $ilToolbar, $DIC;

		$ui_factory = $DIC->ui()->factory();

		$ilTabs->activateTab("content");

		$user_has_write_access = $ilAccess->checkAccess('write','',$this->object->getRefId());

		// Show add appointment
		if($user_has_write_access)
		{
			$add_app_button = $ui_factory->button()->standard(
				ilViteroPlugin::getInstance()->txt('tbbtn_add_appointment'),
				$this->ctrl->getLinkTarget($this,'showAppointmentCreation')
			);

			if($this->canNotCreateAppointmentsByLearningProgressMode())
			{
				$add_app_button = $add_app_button->withUnavailableAction();
				$ilToolbar->addComponent($add_app_button);
				$ilToolbar->addText(ilViteroPlugin::getInstance()->txt("add_appointment_disabled_info"));
			}
			else {
				$ilToolbar->addComponent($add_app_button);
			}


		}

		$this->object->checkInit();

		$table = new ilViteroBookingTableGUI($this,'showContent');
		$table->setVGroupId($this->object->getVGroupId());
		$table->setEditable((bool) $ilAccess->checkAccess('write','',$this->object->getRefId()));
		$table->init();

		$start = new ilDateTime(time(),IL_CAL_UNIX);
		$end = clone $start;
		if($user_has_write_access) {
			$start->increment(ilDateTime::YEAR,-1);
		} else {
			$start->increment(ilDateTime::HOUR,-1);
		}

		$end->increment(IL_CAL_YEAR,1);

		try {
			$table->parse(
				$this->object->getVGroupId(),
				$start,
				$end
			);
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getViteroMessage(),true);
			return false;
		}
		$tpl->setContent($table->getHTML());
	}

	/**
	 * @param $info
	 */
	protected function addInfoStartButton($info)
	{
		global $DIC, $ilCtrl, $ilUser, $ilAccess;
		$user = $DIC->user();

		$access = true;
		if(ilViteroLockedUser::isLocked($ilUser->getId(), $this->object->getVGroupId()))
		{
			ilUtil::sendFailure(ilViteroPlugin::getInstance()->txt('user_locked_info'));
			$access = false;
		}

		\ilChangeEvent::_recordReadEvent(
			$this->object->getType(),
			$this->object->getRefId(),
			$this->object->getId(),
			$user->getId()
		);

		
		// find next booking
		$booking_id = ilViteroUtils::getOpenRoomBooking($this->object->getVGroupId());

		$info_added_section = false;
		if($booking_id)
		{
			// if user is anonymous, check anonymous access
			$access = false;
			if($user->getId() == ANONYMOUS_USER_ID)
			{
				$code = new ilViteroBookingCode(
					$this->object->getVGroupId(),
					$booking_id
				);
				if($code->exists())
				{
					$access = true;
				}
			}
			else
			{
				$access = true;
			}
			
			if($access)
			{
				$this->ctrl->setParameter($this,'bid',$booking_id);
				$info->setFormAction($ilCtrl->getFormAction($this),'_blank');
				$big_button = '<div class="il_ButtonGroup" style="margin:25px; text-align:center; font-size:25px;">'.
					'<input type="submit" formtarget="_blank" class="submit" name="cmd[startSession]" value="'.ilViteroPlugin::getInstance()->txt('start_session').
					'" style="padding:10px;" /></div>';
				$info->addSection("");
				$info->addProperty("", $big_button);

				$info_added_section = true;
			}
		}
		
		// check group access
		if($ilAccess->checkAccess('write','',$this->object->getRefId()))
		{
			$info->setFormAction($ilCtrl->getFormAction($this),'_blank');
			$big_button = '<div class="il_ButtonGroup" style="margin:25px; text-align:center; font-size:25px;">'.
				'<input type="submit" formtarget="_blank" class="submit" name="cmd[startAdminSession]" value="'.ilViteroPlugin::getInstance()->txt('start_admin_session').
				'" style="padding:10px;" /></div>';
			if(!$info_added_section) {
				$info->addSection("");
			}
			$info->addProperty("", $big_button);
		}
	}

	/**
	 * Add info items
	 * @param ilInfoScreenGUI $info 
	 */
	public function addInfoItems($info)
	{
		global $ilCtrl, $ilUser, $ilAccess;
		
		$this->addInfoStartButton($info);

		$start = new ilDateTime(time(),IL_CAL_UNIX);
		$end = clone $start;
		$end->increment(IL_CAL_YEAR,1);

		$booking = ilViteroUtils::lookupNextBooking($start,$end,$this->object->getVGroupId());

		if(!$booking['start'] instanceof  ilDateTime)
		{
			return true;
		}

		ilDatePresentation::setUseRelativeDates(false);

		$info->addSection(ilViteroPlugin::getInstance()->txt('info_next_appointment'));
		$info->addProperty(
			ilViteroPlugin::getInstance()->txt('info_next_appointment_dt'),
			ilDatePresentation::formatPeriod(
				$booking['start'],
				$booking['end']
			)
		);


	}

	public function materials()
	{
        global $DIC;

        $tabs = $DIC->tabs();
        $tabs->activateTab('materials');

        $ui_factory = $DIC->ui()->factory();

        $toolbar = $DIC->toolbar();
        $toolbar->setFormAction($this->ctrl->getFormAction($this));

        $link_button = \ilLinkButton::getInstance();
        $link_button->setCaption($this->getPlugin()->txt('filemanager_start'),false);
        $link_button->setTarget('_blank');
        $link_button->setUrl($this->ctrl->getLinkTarget($this,'showMaterials'));


        $toolbar->addButtonInstance(
            $link_button
        );
    }



	public function showMaterials()
	{
		global $ilUser, $ilTabs, $ilAccess, $ilCtrl;

		$ilTabs->activateTab('materials');

		try {

			// @todo wrap the creation/update of users
			// Create update user
			$map = new ilViteroUserMapping();
			$vuid = $map->getVUserId($ilUser->getId());
			$ucon = new ilViteroUserSoapConnector();
			if(!$vuid)
			{
				$vuid = $ucon->createUser($ilUser);
				$map->map($ilUser->getId(), $vuid);
			}
			else
			{
				try {
					$ucon->updateUser($vuid,$ilUser);
				}
				catch(ilViteroConnectorException $e)
				{
					if($e->getCode() == 53)
					{
						$map->unmap($ilUser->getId());
						$vuid = $ucon->createUser($ilUser);
						$map->map($ilUser->getId(), $vuid);
					}
				}
			}

			// Assign user to vitero group
			$grp = new ilViteroGroupSoapConnector();
			$grp->addUserToGroup($this->object->getVGroupId(), $vuid);

			$grp->changeGroupRole(
				$this->object->getVGroupId(),
				$vuid,
				$ilAccess->checkAccess('write','',$this->object->getRefId()) ?
					ilViteroGroupSoapConnector::ADMIN_ROLE :
					ilViteroGroupSoapConnector::MEMBER_ROLE
			);

			$sc = new ilViteroSessionCodeSoapConnector();
			$dur = new ilDateTime(time(), IL_CAL_UNIX);
			$dur->increment(IL_CAL_HOUR,2);
			$code_vms = $sc->createVmsSessionCode($vuid, $this->object->getVGroupId(),$dur);
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getViteroMessage(),true);
			$ilCtrl->redirect($this,'infoScreen');
		}

		$this->ctrl->redirectToURL(
		    $url = \ilViteroSettings::getInstance()->getGroupFolderLink() .
				'?fl=1&action=reload&topmargin=10&group_id='.$this->object->getVGroupId().'&'.
				'code='.$code_vms
		);
        return;
	}
	
	public function startAdminSession()
	{
		return $this->startSession(true);
	}

	/**
	 * start session
	 * @global <type> $ilDB
	 */
	public function startSession($a_is_admin_session = false)
	{
		global $DIC, $ilCtrl, $ilAccess;
		$ilUser = $DIC->user();

		// Handle deleted accounts
		ilObjVitero::handleDeletedUsers();

		try {

			// Create update user
			$map = new ilViteroUserMapping();
			$vuid = $map->getVUserId($ilUser->getId());
			$ucon = new ilViteroUserSoapConnector();
			if(!$vuid)
			{
				$vuid = $ucon->createUser($ilUser);
				$map->map($ilUser->getId(), $vuid);
			}
			else
			{
				try {
					$ucon->updateUser($vuid,$ilUser);
				}
				catch(ilViteroConnectorException $e)
				{
					if($e->getCode() == 53)
					{
						$map->unmap($ilUser->getId());
						$vuid = $ucon->createUser($ilUser);
						$map->map($ilUser->getId(), $vuid);
					}
				}
			}
			// Store update image
			if(ilViteroSettings::getInstance()->isAvatarEnabled())
			{
				$usr_image_path = ilUtil::getWebspaceDir().'/usr_images/usr_'.$ilUser->getId().'.jpg';
				if(@file_exists($usr_image_path))
				{
					$ucon->storeAvatarUsingBase64(
							$vuid,
							array(
								'name' => 'usr_image.jpg',
								'type' => ilViteroAvatarSoapConnector::FILE_TYPE_NORMAL,
								'file' => $usr_image_path
							)
						);
				}
			}

			// Assign user to vitero group
			$grp = new ilViteroGroupSoapConnector();
			$grp->addUserToGroup($this->object->getVGroupId(), $vuid);

			$grp->changeGroupRole(
				$this->object->getVGroupId(),
				$vuid,
				$ilAccess->checkAccess('write','',$this->object->getRefId()) ? 
					ilViteroGroupSoapConnector::ADMIN_ROLE :
					ilViteroGroupSoapConnector::MEMBER_ROLE
			);

			$sc = new ilViteroSessionCodeSoapConnector();
			$dur = new ilDateTime(time(), IL_CAL_UNIX);
			$dur->increment(IL_CAL_HOUR,2);
			
			if($a_is_admin_session)
			{
				$code = $sc->createPersonalGroupSessionCode(
					$vuid, 
					$this->object->getVGroupId(),
					$dur
				);
			}
			elseif($ilUser->getId() == ANONYMOUS_USER_ID)
			{
				$booking_code = new ilViteroBookingCode(
					$this->object->getVGroupId(),
					(int) $_GET['bid']
				);
				$code = $booking_code->getCode();
			}
			else
			{
				$code = $sc->createPersonalBookingSessionCode($vuid, (int) $_GET['bid'], $dur);
			}

			$GLOBALS['ilLog']->write(__METHOD__.': '.ilViteroSettings::getInstance()->getWebstartUrl().'?code='.$code);
			ilUtil::redirect(ilViteroSettings::getInstance()->getWebstartUrl().'?sessionCode='.$code);
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getViteroMessage(),true);
			$ilCtrl->redirect($this,'infoScreen');
		}
	}

	/**
	 * Show participants
	 */
	protected function participants()
	{
		global $ilTabs, $rbacreview, $ilUser;

		$ilTabs->activateTab('participants');

		$this->addSearchToolbar();

		$tpl = ilViteroPlugin::getInstance()->getTemplate('tpl.edit_participants.html');

		$this->setShowHidePrefs();

		if($rbacreview->assignedUsers((int) $this->object->getDefaultAdminRole()))
		{
			if($ilUser->getPref('xvit_admin_hide'))
			{
				$table_gui = new ilViteroParticipantsTableGUI($this,ilObjVitero::ADMIN,false);
				$table_gui->setVGroupId($this->object->getVGroupId());
				$this->ctrl->setParameter($this,'admin_hide',0);
				$table_gui->addHeaderCommand($this->ctrl->getLinkTarget($this,'participants'),
					$this->lng->txt('show'));
				$this->ctrl->clearParameters($this);
			}
			else
			{
				$table_gui = new ilViteroParticipantsTableGUI($this,ilObjVitero::ADMIN,true);
				$table_gui->setVGroupId($this->object->getVGroupId());
				$this->ctrl->setParameter($this,'admin_hide',1);
				$table_gui->addHeaderCommand($this->ctrl->getLinkTarget($this,'participants'),
					$this->lng->txt('hide'));
				$this->ctrl->clearParameters($this);
			}
			$table_gui->setTitle(
				ilViteroPlugin::getInstance()->txt('admins'),
				'icon_usr.svg',$this->lng->txt('grp_admins'));
			$table_gui->parse($rbacreview->assignedUsers((int) $this->object->getDefaultAdminRole()));
			$tpl->setVariable('ADMINS',$table_gui->getHTML());
		}


		if($rbacreview->assignedUsers((int) $this->object->getDefaultMemberRole()))
		{
			if($ilUser->getPref('xvit_member_hide'))
			{
				$table_gui = new ilViteroParticipantsTableGUI($this,ilObjVitero::MEMBER,false);
				$table_gui->setVGroupId($this->object->getVGroupId());
				$this->ctrl->setParameter($this,'member_hide',0);
				$table_gui->addHeaderCommand($this->ctrl->getLinkTarget($this,'participants'),
					$this->lng->txt('show'));
				$this->ctrl->clearParameters($this);
			}
			else
			{
				$table_gui = new ilViteroParticipantsTableGUI($this,ilObjVitero::MEMBER,true);
				$table_gui->setVGroupId($this->object->getVGroupId());
				$this->ctrl->setParameter($this,'member_hide',1);
				$table_gui->addHeaderCommand($this->ctrl->getLinkTarget($this,'participants'),
					$this->lng->txt('hide'));
				$this->ctrl->clearParameters($this);
			}

			$table_gui->setTitle(
				ilViteroPlugin::getInstance()->txt('participants'),
				'icon_usr.svg',$this->lng->txt('grp_members'));
			$table_gui->parse($rbacreview->assignedUsers((int) $this->object->getDefaultMemberRole()));
			$tpl->setVariable('MEMBERS',$table_gui->getHTML());

		}
		$remove = ilSubmitButton::getInstance();
		$remove->setCommand("confirmDeleteParticipants");
		$remove->setCaption("remove",true);
		$tpl->setVariable('BTN_REMOVE',$remove->render());
		if(ilViteroLockedUser::hasLockedAccounts($this->object->getVGroupId()))
		{
		$unlock = ilSubmitButton::getInstance();
		$unlock->setCommand("unlockUsers");
		$unlock->setCaption(ilViteroPlugin::getInstance()->txt('btn_unlock'),false);
		$tpl->setVariable('BTN_UNLOCK',$unlock->render());
		}
		$lock = ilSubmitButton::getInstance();
		$lock->setCommand("lockUsers");
		$lock->setCaption(ilViteroPlugin::getInstance()->txt('btn_lock'),false);
		$tpl->setVariable('BTN_LOCK',$lock->render());
		$mail = ilSubmitButton::getInstance();
		$mail->setCommand("sendMailToSelectedUsers");
		$mail->setCaption("grp_mem_send_mail",true);
		$tpl->setVariable('BTN_MAIL',$mail->render());

		$tpl->setVariable('ARROW_DOWN',ilUtil::getImagePath('arrow_downright.svg'));
		$tpl->setVariable('FORMACTION',$this->ctrl->getFormAction($this));

		$GLOBALS['tpl']->setContent($tpl->get());
	}

	/**
	 * Unlock accounts
	 * @return bool
	 */
	protected function unlockUsers()
	{
		$this->tabs_gui->setTabActive('participants');

		$participants_to_unlock = (array) array_unique(array_merge((array) $_POST['admins'],(array) $_POST['members']));

		if(!count($participants_to_unlock))
		{
			ilUtil::sendFailure($this->lng->txt('no_checkbox'));
			$this->participants();
			return false;
		}

		foreach($participants_to_unlock as $part)
		{
			$unlock = new ilViteroLockedUser();
			$unlock->setUserId($part);
			$unlock->setVGroupId($this->object->getVGroupId());
			$unlock->setLocked(false);
			$unlock->update();
		}

		$grp = new ilViteroGroupSoapConnector();
		$grp->updateEnabledStatusForUsers($participants_to_unlock,$this->object->getVGroupId(),true);

		ilUtil::sendSuccess($GLOBALS['lng']->txt('settings_saved'),true);
		$GLOBALS['ilCtrl']->redirect($this,'participants');
	}

	/**
	 * Unlock accounts
	 * @return bool
	 */
	protected function lockUsers()
	{
		$this->tabs_gui->setTabActive('participants');

		$participants_to_unlock = (array) array_unique(array_merge((array) $_POST['admins'],(array) $_POST['members']));

		if(!count($participants_to_unlock))
		{
			ilUtil::sendFailure($this->lng->txt('no_checkbox'));
			$this->participants();
			return false;
		}

		foreach($participants_to_unlock as $part)
		{
			$unlock = new ilViteroLockedUser();
			$unlock->setUserId($part);
			$unlock->setVGroupId($this->object->getVGroupId());
			$unlock->setLocked(true);
			$unlock->update();
		}

		$grp = new ilViteroGroupSoapConnector();
		$grp->updateEnabledStatusForUsers($participants_to_unlock,$this->object->getVGroupId(),false);

		ilUtil::sendSuccess($GLOBALS['lng']->txt('settings_saved'),true);
		$GLOBALS['ilCtrl']->redirect($this,'participants');
	}

	protected function confirmDeleteParticipants()
	{
		$this->tabs_gui->setTabActive('participants');

		$participants_to_delete = (array) array_unique(array_merge((array) $_POST['admins'],(array) $_POST['members']));

		if(!count($participants_to_delete))
		{
			ilUtil::sendFailure($this->lng->txt('no_checkbox'));
			$this->participants();
			return false;
		}


		$this->lng->loadLanguageModule('grp');

		include_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
		$confirm = new ilConfirmationGUI();
		$confirm->setFormAction($this->ctrl->getFormAction($this,'deleteParticipants'));
		$confirm->setHeaderText($this->lng->txt('grp_dismiss_member'));
		$confirm->setConfirm($this->lng->txt('confirm'),'deleteParticipants');
		$confirm->setCancel($this->lng->txt('cancel'),'participants');

		foreach($participants_to_delete as $participant)
		{
			$names = ilObjUser::_lookupName($participant);



			$confirm->addItem('participants[]',
				$participant,
				$names['lastname'].', '.$names['firstname'].' ['.$names['login'].']',
				ilUtil::getImagePath('icon_usr.svg'));
		}
		$this->tpl->setContent($confirm->getHTML());
	}

	/**
	 * Delete participants
	 */
	protected function  deleteParticipants()
	{
		global $rbacadmin,$lng;

		if(!count($_POST['participants']))
		{
			ilUtil::sendFailure($this->lng->txt('no_checkbox'));
			$this->participants();
			return true;
		}

		foreach((array) $_POST['participants'] as $part)
		{
			$rbacadmin->deassignUser($this->object->getDefaultAdminRole(),$part);
			$rbacadmin->deassignUser($this->object->getDefaultMemberRole(),$part);

			$locked = new ilViteroLockedUser();
			$locked->setUserId($part);
			$locked->setVGroupId($this->object->getVGroupId());
			$locked->delete();
		}

		$lng->loadLanguageModule('grp');
		ilUtil::sendSuccess($this->lng->txt("grp_msg_membership_annulled"));
		$this->participants();
		return true;
	}

	protected function sendMailToSelectedUsers()
	{
		$_POST['participants'] = array_unique(array_merge((array) $_POST['admins'],(array) $_POST['members']));
		if (!count($_POST['participants']))
		{
			ilUtil::sendFailure($this->lng->txt("no_checkbox"));
			$this->participants();
			return false;
		}
		foreach($_POST['participants'] as $usr_id)
		{
			$rcps[] = ilObjUser::_lookupLogin($usr_id);
		}
        require_once 'Services/Mail/classes/class.ilMailFormCall.php';
		ilUtil::redirect(ilMailFormCall::getRedirectTarget(
			$this,
			'participants',
			array(),
			array('type' => 'new', 'rcp_to' => implode(',',$rcps))));
		return true;
	}

	/**
	 * set preferences (show/hide tabel content)
	 *
	 * @access public
	 * @return
	 */
	public function setShowHidePrefs()
	{
		global $ilUser;

		if(isset($_GET['admin_hide']))
		{
			$ilUser->writePref('xvit_admin_hide',(int) $_GET['admin_hide']);
		}
		if(isset($_GET['member_hide']))
		{
			$ilUser->writePref('xvit_member_hide',(int) $_GET['member_hide']);
		}
	}

	protected function addSearchToolbar()
	{
		global $ilToolbar,$lng;

		$lng->loadLanguageModule('crs');

		// add members
		include_once './Services/Search/classes/class.ilRepositorySearchGUI.php';
		ilRepositorySearchGUI::fillAutoCompleteToolbar(
			$this,
			$ilToolbar,
			array(
				'auto_complete_name'	=> $lng->txt('user'),
				'user_type'				=> array(
					ilObjVitero::MEMBER => ilViteroPlugin::getInstance()->txt('member'),
					ilObjVitero::ADMIN => ilViteroPlugin::getInstance()->txt('admin')
				),
				'submit_name'			=> $lng->txt('add')
			)
		);

		// spacer
		$ilToolbar->addSeparator();

		// search button
		$ilToolbar->addButton(
			$this->lng->txt("crs_search_users"),
			$this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI','start')
		);
		return true;

	}

	/**
	 * Callback for ilRepositorySearchGUI
	 * @param array $a_user_ids
	 * @param int $a_type
	 */
	public function addParticipants($a_user_ids, $a_type)
	{
		try {
			$this->object->addParticipants($a_user_ids, $a_type);
		}
		catch(InvalidArgumentException $e)
		{
			ilUtil::sendFailure($e->getMessage());
			return false;
		}
		ilUtil::sendSuccess(ilViteroPlugin::getInstance()->txt('assigned_users'));
		$this->ctrl->redirect($this,'participants');
	}

	//TODO add one step before validating if the user can delete the sessions or not depending on LP mode configuration.
	public function confirmDeleteBooking()
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('content');

		try {

			$booking_service = new ilViteroBookingSoapConnector();
			$book = $booking_service->getBookingById($_REQUEST['bookid']);
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getMessage(), true);
			$GLOBALS['ilCtrl']->redirect($this,'showContent');
		}

		$this->controlDeletionByLearningModuleMode($book);

		include_once './Services/Utilities/classes/class.ilConfirmationGUI.php';
		$confirm = new ilConfirmationGUI();
		$confirm->setFormAction($GLOBALS['ilCtrl']->getFormAction($this));
		$confirm->setHeaderText(ilViteroPlugin::getInstance()->txt('sure_delete_appointment_series'));

		$start = ilViteroUtils::parseSoapDate($book->booking->start);

		ilDatePresentation::setUseRelativeDates(false);

		$confirm->addItem(
			'bookid[]',
			(int) $_REQUEST['bookid'],
			sprintf(
				ilViteroPlugin::getInstance()->txt('confirm_delete_series_txt'),
				ilDatePresentation::formatDate($start),
				ilViteroUtils::recurrenceToString($book->booking->repetitionpattern)
			)
		);

		$confirm->setConfirm($GLOBALS['lng']->txt('delete'), 'deleteBooking');
		$confirm->setCancel($GLOBALS['lng']->txt('cancel'), 'showContent');
		$tpl->setContent($confirm->getHTML());
	}

	/**
	 * @param $book ViteroBookingResponse
	 * return void
	 */
	protected function controlDeletionByLearningModuleMode($book)
	{
		if($this->object->isLearningProgressActive())
		{
			$total_appointments = $this->object->getNumberOfAppointmentsForSession();
			$start = new ilDateTime(time(),IL_CAL_UNIX);
			$end = clone $start;
			$start->increment(IL_CAL_YEAR,-5);
			$end->increment(IL_CAL_YEAR,1);

			$number_app_to_delete = 0;
			foreach (ilViteroUtils::calculateBookingAppointments($start, $end, $book->booking) as $dl) {
				$number_app_to_delete++;
			}

			$appointments_after_deletion = $total_appointments - $number_app_to_delete;

			if ($this->object->isLearningProgressModeMultiActive() && $appointments_after_deletion < 2) {
				ilUtil::sendFailure(ilViteroPlugin::getInstance()->txt('delete_info_minimum_app'), true);
				$GLOBALS['ilCtrl']->redirect($this, 'showContent');
			}

			if (!$this->object->isLearningProgressModeMultiActive() && $appointments_after_deletion < 1) {
				ilUtil::sendFailure(ilViteroPlugin::getInstance()->txt('delete_info_disable_lp'), true);
				$GLOBALS['ilCtrl']->redirect($this, 'showContent');
			}
		}

		return;

	}

	protected function deleteBooking()
	{
		foreach((array) $_REQUEST['bookid'] as $bookid)
		{
			try {
				$booking_service = new ilViteroBookingSoapConnector();
				$booking_service->deleteBooking($bookid);
			}
			catch(ilViteroConnectorException $e)
			{
				ilUtil::sendFailure($e->getMessage(),true);
				$GLOBALS['ilCtrl']->redirect($this,'showContent');
			}
		}
		ilUtil::sendSuccess(ilViteroPlugin::getInstance()->txt('deleted_booking'));
		$GLOBALS['ilCtrl']->redirect($this,'showContent');
	}

	protected function deleteBookingInSeries()
	{
		foreach((array) $_REQUEST['bookid'] as $bookid)
		{
			$excl = new ilViteroBookingReccurrenceExclusion();
			$excl->setDate(new ilDate($_REQUEST['atime'],IL_CAL_UNIX));
			$excl->setEntryId($bookid);
			$excl->save();
		}
		ilUtil::sendSuccess(ilViteroPlugin::getInstance()->txt('deleted_booking'));
		$GLOBALS['ilCtrl']->redirect($this,'showContent');
	}

	protected function confirmDeleteAppointment($inRecurrence = false)
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('content');

		try {
			$booking_service = new ilViteroBookingSoapConnector();
			$book = $booking_service->getBookingById($_REQUEST['bookid']);
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getMessage(), true);
			$GLOBALS['ilCtrl']->redirect($this,'showContent');
		}

		$this->controlDeletionByLearningModuleMode($book);

		include_once './Services/Utilities/classes/class.ilConfirmationGUI.php';
		$confirm = new ilConfirmationGUI();
		$confirm->setFormAction($GLOBALS['ilCtrl']->getFormAction($this));
		$confirm->setHeaderText(ilViteroPlugin::getInstance()->txt('sure_delete_appointment'));

		if($inRecurrence)
		{
			$start = new ilDateTime($_REQUEST['atime'],IL_CAL_UNIX);
			$confirm->setConfirm($GLOBALS['lng']->txt('delete'), 'deleteBookingInSeries');
		}
		else
		{
			$start = ilViteroUtils::parseSoapDate($book->booking->start);
			$confirm->setConfirm($GLOBALS['lng']->txt('delete'), 'deleteBooking');
		}

		ilDatePresentation::setUseRelativeDates(false);

		$confirm->addItem(
			'bookid[]',
			(int) $_REQUEST['bookid'],
			ilDatePresentation::formatDate($start)
		);

		if($inRecurrence)
		{
			$confirm->addHiddenItem('atime', $_REQUEST['atime']);
		}

		$confirm->setCancel($GLOBALS['lng']->txt('cancel'), 'showContent');

		$tpl->setContent($confirm->getHTML());
	}

	public function confirmDeleteAppointmentInSeries()
	{

		$this->confirmDeleteAppointment(true);

	}

	/**
	 * @param bool $a_create
	 * @return ilPropertyFormGUI
	 */
	protected function initAppointmentCreationForm($a_create = true)
	{
		global $lng;

		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));


		if($a_create)
		{
			$form->setTitle(ilViteroPlugin::getInstance()->txt('tbl_add_appointment'));
			$form->addCommandButton(
				'createAppointment',
				ilViteroPlugin::getInstance()->txt('btn_add_appointment')
			);
		}
		else
		{
			$form->setTitle(ilViteroPlugin::getInstance()->txt('tbl_update_appointment'));
			$form->addCommandButton(
				'updateBooking',
				ilViteroPlugin::getInstance()->txt('save')
			);

		}

		$form->addCommandButton(
			'showContent',
			$GLOBALS['lng']->txt('cancel')
		);

		$settings = ilViteroSettings::getInstance();
		// show selection
		if($settings->isCafeEnabled() and $settings->isStandardRoomEnabled())
		{
			$type_select = new ilRadioGroupInputGUI(
				ilViteroPlugin::getInstance()->txt('app_type'),
				'atype'
			);

			if(!$a_create)
			{
				$type_select->setDisabled(true);
			}

			$type_select->setValue(ilViteroRoom::TYPE_CAFE);

			// Cafe
			$cafe = new ilRadioOption(
				ilViteroPlugin::getInstance()->txt('app_type_cafe'),
				ilViteroRoom::TYPE_CAFE
			);
			$type_select->addOption($cafe);

			$this->initFormCafe($cafe,$a_create);

			// Standard
			$std = new ilRadioOption(
				ilViteroPlugin::getInstance()->txt('app_type_standard'),
				ilViteroRoom::TYPE_STD
			);
			$type_select->addOption($std);

			$this->initFormStandardRoom($std,$a_create);


			$form->addItem($type_select);
		}
		elseif($settings->isCafeEnabled())
		{
			$this->initFormCafe($form,$a_create);
		}
		elseif($settings->isStandardRoomEnabled())
		{
			$this->initFormStandardRoom($form,$a_create);
		}

		$this->initFormTimeBuffer($form);

		$this->initFormPhone($form);
		$this->initFormRecorder($form);
		$this->initFormMobileAccess($form);
		$this->initFormAnonymousAccess($form);
		$this->initFormRoomSize($form,$a_create);

		return $form;
	}

	protected function showAppointmentCreation()
	{
		global $ilTabs;

		$ilTabs->activateTab('content');

		$form = $this->initAppointmentCreationForm();

		$GLOBALS['tpl']->setContent($form->getHTML());
	}

	protected function createAppointment()
	{
		global $ilTabs;

		$form = $this->initAppointmentCreationForm();

		$ilTabs->activateTab('content');

		if(!$form->checkInput())
		{
			$form->setValuesByPost();
			ilUtil::sendFailure(
				$this->lng->txt('err_check_input')
			);
			$GLOBALS['tpl']->setContent($form->getHTML());
			return false;
		}


		// Save and create appointment
		$settings = ilViteroSettings::getInstance();

		$room = new ilViteroRoom();
		$room->enableRecorder($form->getInput('recorder'));

		$phone = new ilViteroPhone();
		$phone->initFromForm($form);
		$room->setPhone($phone);
		$room->setRoomSize($form->getInput('room_size'));

		if($settings->isCafeEnabled() and $settings->isStandardRoomEnabled())
		{
			if($form->getInput('atype') == ilViteroRoom::TYPE_CAFE)
			{
				$room = $this->loadCafeSettings($form, $room);
			}
			else
			{
				$room = $this->loadStandardRoomSettings($form, $room);
			}

			$room->isCafe($form->getInput('atype') == ilViteroRoom::TYPE_CAFE);
		}
		elseif($settings->isCafeEnabled())
		{
			$this->loadCafeSettings($form, $room);
		}
		else
		{
			$this->loadStandardRoomSettings($form, $room);
		}

		try {
			$this->object->initAppointment(
				$room,
				(bool) $form->getInput('anonymous_access'),
				(bool) $form->getInput('mobile')
			);
			ilUtil::sendSuccess(ilViteroPlugin::getInstance()->txt('created_vitero'), true);
			$this->ctrl->redirect($this,'showContent');
			return true;
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getViteroMessage(),true);
			$form->setValuesByPost();
			$GLOBALS['tpl']->setContent($form->getHTML());
		}
	}

	protected function editBooking()
	{
		global $ilTabs;

		$this->ctrl->setParameter($this,'bookid',(int) $_REQUEST['bookid']);

		$ilTabs->activateTab('content');

		try {

			$booking_service = new ilViteroBookingSoapConnector();
			$booking = $booking_service->getBookingById((int) $_REQUEST['bookid']);
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getMessage(),true);
			$this->ctrl->redirect($this,'showContent');
		}
		$form = $this->initUpdateBookingForm($booking);
		$GLOBALS['tpl']->setContent($form->getHTML());
	}

	/**
	 * @param $booking
	 * @return ilPropertyFormGUI
	 */
	protected function initUpdateBookingForm($booking)
	{
		global $lng;

		$lng->loadLanguageModule('dateplaner');
		$lng->loadLanguageModule('crs');

		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this,'showContent'));
		$form->setTitle(ilViteroPlugin::getInstance()->txt('tbl_update_appointment'));
		$form->addCommandButton('updateBooking', $GLOBALS['lng']->txt('save'));
		$form->addCommandButton('showContent', $GLOBALS['lng']->txt('cancel'));


		// Show only start if type is "cafe"
		if($booking->booking->cafe)
		{
			$start = new ilDateTimeInputGUI($this->getPlugin()->txt('event_start_date'), 'cstart');
			$start->setShowTime(false);
			$start->setDate(ilViteroUtils::parseSoapDate($booking->booking->start));
			$form->addItem($start);
		}
		else
		{
			include_once './Services/Form/classes/class.ilDateDurationInputGUI.php';
			$dt = new ilDateDurationInputGUI($lng->txt('cal_fullday'), 'roomduration');
			$dt->setMinuteStepSize(15);
			$dt->setStartText($this->getPlugin()->txt('event_start_date'));
			$dt->setEndText($this->getPlugin()->txt('event_end_date'));
			$dt->setShowTime(true);

			$dt->setStart(ilViteroUtils::parseSoapDate($booking->booking->start));
			$dt->setEnd(ilViteroUtils::parseSoapDate($booking->booking->end));

			$form->addItem($dt);
	
			$this->initFormTimeBuffer($form);

			$form->getItemByPostVar('buffer_before')->setValue($booking->booking->startbuffer);
			$form->getItemByPostVar('buffer_after')->setValue($booking->booking->endbuffer);
		}
		$this->initFormMobileAccess($form, $booking->booking->bookingid);
		$this->initFormAnonymousAccess($form, $booking->booking->bookingid);

		return $form;
	}

	/**
	 * Update a single booking
	 * @return bool
	 */
	protected function updateBooking()
	{
		global $ilTabs;

		$ilTabs->activateTab('content');
		$this->ctrl->setParameter($this,'bookid',(int) $_REQUEST['bookid']);

		try {
			$booking_service = new ilViteroBookingSoapConnector();
			$booking = $booking_service->getBookingById((int) $_REQUEST['bookid']);
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getMessage(),true);
			$this->ctrl->redirect($this,'showContent');
		}

		$form = $this->initUpdateBookingForm($booking);

		if(!$form->checkInput())
		{
			$form->setValuesByPost();
			ilUtil::sendFailure(
				$this->lng->txt('err_check_input')
			);
			$GLOBALS['tpl']->setContent($form->getHTML());
			return false;
		}

		$room = new ilViteroRoom();
		$room->setBookingId($booking->booking->bookingid);
		$room->setBufferBefore((int) $_POST['buffer_before']);
		$room->setBufferAfter((int) $_POST['buffer_after']);

		// Set end date for cafe room
		if($booking->booking->cafe)
		{
			$start = $form->getItemByPostVar('cstart')->getDate();
			$end = clone $start;
			$end->increment(IL_CAL_DAY,1);

			$room->setStart($start);
			$room->setEnd($end);
		}
		else
		{
			$start = $form->getItemByPostVar('roomduration')->getStart();
			$end = $form->getItemByPostVar('roomduration')->getEnd();

			$room->setStart($start);
			$room->setEnd($end);
		}

		$this->object->handleMobileAccess(
			(bool) $form->getInput('mobile'),
			(int) $_REQUEST['bookid']
		);

		// handle update of anonymous access
		$code = new ilViteroBookingCode(
			$this->object->getVGroupId(),
			(int) $_REQUEST['bookid']
		);
		
		$code_checked = (int) $_POST['anonymous_access'];
		
		// delete code if
		if(!$code_checked && $code->exists())
		{
			try {
				$con = new ilViteroSessionCodeSoapConnector();
				$con->deleteSessionCodes([(int) $_REQUEST['bookid']]);
				$code->delete();
			} 
			catch (ilViteroConnectorException $e) {
				ilUtil::sendFailure($e->getViteroMessage(),true);
				$form->setValuesByPost();
				$GLOBALS['tpl']->setContent($form->getHTML());
			}
		}
		// add code if
		elseif($code_checked && !$code->exists())
		{
			try {
				$session = new ilViteroSessionCodeSoapConnector();
				$session->createBookingSessionCode((int) $_REQUEST['bookid'], $this->object->getVGroupId());
			} 
			catch (ilViteroConnectorException $e) {
				ilUtil::sendFailure($e->getViteroMessage(),true);
				$form->setValuesByPost();
				$GLOBALS['tpl']->setContent($form->getHTML());
			}
		}

		try {
			$con = new ilViteroBookingSoapConnector();

			$con->updateBooking($room, $this->object->getVGroupId());
			ilUtil::sendSuccess($GLOBALS['lng']->txt('settings_saved'), true);
			$this->ctrl->redirect($this,'showContent');
			return true;
		}
		catch(ilViteroConnectorException $e)
		{
			ilUtil::sendFailure($e->getViteroMessage(),true);
			$form->setValuesByPost();
			$GLOBALS['tpl']->setContent($form->getHTML());
		}
	}

	/**
	 * Returns false if learning progress has mode "one session" and we have an appointment already
	 * @return bool
	 */
	public function canNotCreateAppointmentsByLearningProgressMode()
	{
		if($this->object->isLearningProgressActive()) {
			if (!$this->object->isLearningProgressModeMultiActive() && ($this->object->getNumberOfAppointmentsForSession() >= 1)) {
				return true;
			}
		}

		return false;
	}
}
?>