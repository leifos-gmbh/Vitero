<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id: class.ilViteroParticipantsTableGUI.php 33166 2012-02-14 13:49:39Z smeyer $
*
*/

class ilViteroParticipantsTableGUI extends ilTable2GUI
{
    protected $type = ilObjVitero::ADMIN;
    
    protected static $export_allowed = false;
    protected static $confirmation_required = true;
    protected static $accepted_ids = null;
	
	protected static $all_columns = null;

	private $vgroup_id;

    /**
     * Constructor
     *
     * @access public
     * @param
     * @return
     */
    public function __construct($a_parent_obj,$a_type = 'admin',$show_content = true)
    {
        global $lng,$ilCtrl;
        
        $this->lng = $lng;
        $this->lng->loadLanguageModule('grp');
        $this->lng->loadLanguageModule('trac');
        $this->ctrl = $ilCtrl;
        $this->type = $a_type; 
        
        
        $this->setId('xvit_'.$a_type.'_'.$a_parent_obj->object->getId());
        parent::__construct($a_parent_obj,'participants');
		
        $this->setFormName('participants');

        $this->addColumn('','f',"1");
		$this->addColumn($this->lng->txt('login'),'login','10%');
        $this->addColumn($this->lng->txt('name'),'lastname','80%');
		$this->addColumn(ilViteroPlugin::getInstance()->txt('account_unlocked'),'4em');
        

        if($this->type == ilObjVitero::ADMIN)
        {
            $this->setPrefix('admin');
            $this->setSelectAllCheckbox('admins');
        }
        else
        {
            $this->setPrefix('member');
            $this->setSelectAllCheckbox('members');
        }
        #$this->addColumn($this->lng->txt(''),'optional');
        $this->setDefaultOrderField('lastname');
        $this->setRowTemplate("tpl.show_participants_row.html",substr(ilViteroPlugin::getInstance()->getDirectory(),2));
        
        if($show_content)
        {
            $this->enable('sort');
            $this->enable('header');
            $this->enable('numinfo');
            $this->enable('select_all');
        }
        else
        {
            $this->disable('content');
            $this->disable('header');
            $this->disable('footer');
            $this->disable('numinfo');
            $this->disable('select_all');
        }       
    }

	public function getVGroupId()
	{
		return $this->vgroup_id;
	}

	public function setVGroupId($a_id)
	{
		$this->vgroup_id = $a_id;
	}

    
    /**
     * fill row 
     *
     * @access public
     * @param
     * @return
     */
    public function fillRow($a_set)
    {
        global $ilUser,$ilAccess;
        
        $this->tpl->setVariable('VAL_ID',$a_set['usr_id']);
        $this->tpl->setVariable('VAL_NAME',$a_set['lastname'].', '.$a_set['firstname']);
        if(!$ilAccess->checkAccessOfUser($a_set['usr_id'],'read','',$this->getParentObject()->object->getRefId()) and 
            is_array($info = $ilAccess->getInfo()))
        {
			$this->tpl->setCurrentBlock('access_warning');
			$this->tpl->setVariable('PARENT_ACCESS',$info[0]['text']);
			$this->tpl->parseCurrentBlock();
        }

		if(!ilObjUser::_lookupActive($a_set['usr_id']))
		{
			$this->tpl->setCurrentBlock('access_warning');
			$this->tpl->setVariable('PARENT_ACCESS',$this->lng->txt('usr_account_inactive'));
			$this->tpl->parseCurrentBlock();
		}

        
        foreach($this->getSelectedColumns() as $field)
        {
            switch($field)
            {
                case 'gender':
                    $a_set['gender'] = $a_set['gender'] ? $this->lng->txt('gender_'.$a_set['gender']) : '';                 
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST',$a_set[$field]);
                    $this->tpl->parseCurrentBlock();
                    break;
                    
                case 'birthday':
                    $a_set['birthday'] = $a_set['birthday'] ? ilDatePresentation::formatDate(new ilDate($a_set['birthday'],IL_CAL_DATE)) : $this->lng->txt('no_date');              
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST',$a_set[$field]);
                    $this->tpl->parseCurrentBlock();
                    break;
                                        
                default:
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST',$a_set[$field] ? $a_set[$field] : '');
                    $this->tpl->parseCurrentBlock();
                    break;
            }
        }

		if($a_set['locked'])
		{
			$this->tpl->setVariable('UNLOCKED_ALT', ilViteroPlugin::getInstance()->txt('locked_txt'));
			$this->tpl->setVariable('UNLOCKED_IMG',ilUtil::getImagePath('icon_not_ok.gif'));
		}
		else
		{
			$this->tpl->setVariable('UNLOCKED_ALT', ilViteroPlugin::getInstance()->txt('unlocked_txt'));
			$this->tpl->setVariable('UNLOCKED_IMG',ilUtil::getImagePath('icon_ok.gif'));

		}
		
        if($this->type == ilObjVitero::ADMIN)
        {
            $this->tpl->setVariable('VAL_POSTNAME','admins');
        }
        else
        {
            $this->tpl->setVariable('VAL_POSTNAME','members');
        }
        
        $this->ctrl->setParameter($this->parent_obj,'member_id',$a_set['usr_id']);
        $this->tpl->setVariable('LINK_NAME',$this->ctrl->getLinkTarget($this->parent_obj,'editMember'));
        $this->tpl->setVariable('LINK_TXT',$this->lng->txt('edit'));
        $this->ctrl->clearParameters($this->parent_obj);
        
        $this->tpl->setVariable('VAL_LOGIN',$a_set['login']);
    }
    
    /**
     * Parse user data
     * @param array $a_user_data
     * @return 
     */
    public function parse($part)
    {
        include_once './Services/User/classes/class.ilUserQuery.php';
        
        $usr_data = ilUserQuery::getUserListData(
            'login',
            'ASC',
            0,
            9999,
            '',
            '',
            null,
            false,
            false,
            0,
            0,
            null,
            array(),
            $part
        );

		$locked = ilViteroLockedUser::getLockedAccounts($this->getVGroupId());

		$users = array();
		foreach((array) $usr_data['set'] as $key => $usr)
		{
			if(in_array($usr['usr_id'], (array) $locked))
			{
				$usr['locked'] = 1;
			}
			else
			{
				$usr['locked'] = 0;
			}

			$users[] = $usr;
		}
        return $this->setData((array) $users);
    }
    
}
?>
