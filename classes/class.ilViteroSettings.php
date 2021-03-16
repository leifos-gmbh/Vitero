<?php

/**
 * Global vitero settings
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroSettings.php 36242 2012-08-15 13:00:21Z smeyer $
 */
class ilViteroSettings
{
    const PHONE_CONFERENCE = 1;
    const PHONE_DIAL_OUT = 2;
    const PHONE_DIAL_OUT_PART = 3;

    private static $instance = null;

    private $storage = null;

    private $url = 'http://yourserver.de/vitero/services';
    private $webstart = 'http://yourserver.de/vitero/start.htm';
    private $admin = '';
    private $pass = '';

    private $customer = null;
    private $use_ldap = false;
    private $enable_cafe = false;
    private $enable_content = false;
    private $enable_standard_room = true;
    private $user_prefix = 'il_';
    private $avatar = 0;
    private $mtom_cert = '';
    private $phone_conference = false;
    private $phone_dial_out = false;
    private $phone_dial_out_part = false;
    private $mobile_access_enabled = false;
    private $session_recorder = false;
    private $enable_learning_progress = false;
    private $inspire = false;

    private $grace_period_before = 15;
    private $grace_period_after = 15;

    /**
     * @var bool
     */
    private $file_handling_ilias_enabled = false;



    /**
     * @var bool
     */
    private $file_handling_vitero_enabled = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->storage = new ilSetting('vitero_config');
        $this->read();
    }

    /**
     * Read settings
     */
    protected function read()
    {
        $this->setServerUrl($this->getStorage()->get('server', $this->url));
        $this->setAdminUser($this->getStorage()->get('admin', $this->admin));
        $this->setAdminPass($this->getStorage()->get('pass', $this->pass));
        $this->setCustomer($this->getStorage()->get('customer', $this->customer));
        $this->useLdap($this->getStorage()->get('ldap', $this->use_ldap));
        $this->enableCafe($this->getStorage()->get('cafe', $this->enable_cafe));
        $this->enableContentAdministration($this->getStorage()->get('content', $this->enable_content));
        $this->enableStandardRoom($this->getStorage()->get('std_room', $this->enable_standard_room));
        $this->setWebstartUrl($this->getStorage()->get('webstart', $this->webstart));
        $this->setUserPrefix($this->getStorage()->get('uprefix', $this->user_prefix));
        $this->setStandardGracePeriodBefore($this->getStorage()->get('grace_period_before', $this->grace_period_before));
        $this->setStandardGracePeriodAfter($this->getStorage()->get('grace_period_after', $this->grace_period_after));
        $this->enableAvatar($this->getStorage()->get('avatar', $this->avatar));
        $this->setMTOMCert($this->getStorage()->get('mtom_cert', $this->mtom_cert));
        $this->enablePhoneConference($this->getStorage()->get('phone_conference', $this->isPhoneConferenceEnabled()));
        $this->enablePhoneDialOut($this->getStorage()->get('phone_dial_out', $this->isPhoneDialOutEnabled()));
        $this->enablePhoneDialOutParticipants($this->getStorage()->get('phone_dial_out_participants', $this->isPhoneDialOutParticipantsEnabled()));
        $this->enableMobileAccess($this->getStorage()->get('mobile', $this->isMobileAccessEnabled()));
        $this->enableSessionRecorder($this->getStorage()->get('recorder', $this->isSessionRecorderEnabled()));
        $this->enableLearningProgress($this->getStorage()->get('learning_progress', $this->isLearningProgressEnabled()));
        $this->setInspireSelectable($this->getStorage()->get('inspire', $this->isInspireSelectable()));
        $this->setFileHandlingIliasEnabled($this->getStorage()->get('file_handling_ilias',$this->isFileHandlingIliasEnabled()));
        $this->setFileHandlingViteroEnabled($this->getStorage()->get('file_handling_vitero', $this->isFileHandlingViteroEnabled()));
    }

    public function setServerUrl($a_url)
    {
        $this->url = $a_url;
    }

    /**
     * Get storage
     * @return ilSetting
     */
    public function getStorage()
    {
        return $this->storage;
    }

    public function setAdminUser($a_admin)
    {
        $this->admin = $a_admin;
    }

    public function setAdminPass($a_pass)
    {
        $this->pass = $a_pass;
    }

    public function useLdap($a_stat)
    {
        $this->use_ldap = $a_stat;
    }

    public function enableCafe($a_stat)
    {
        $this->enable_cafe = $a_stat;
    }

    public function enableContentAdministration($a_stat)
    {
        $this->enable_content = $a_stat;
    }

    public function enableStandardRoom($a_stat)
    {
        $this->enable_standard_room = $a_stat;
    }

    public function setWebstartUrl($a_url)
    {
        $this->webstart = $a_url;
    }

    public function setStandardGracePeriodBefore($a_val)
    {
        $this->grace_period_before = $a_val;
    }

    public function setStandardGracePeriodAfter($a_val)
    {
        $this->grace_period_after = $a_val;
    }

    public function enableAvatar($a_stat)
    {
        $this->avatar = $a_stat;
    }

    /**
     * @param $a_stat
     */
    public function enablePhoneConference($a_stat)
    {
        $this->phone_conference = $a_stat;
    }

    /**
     * @return bool
     */
    public function isPhoneConferenceEnabled()
    {
        return $this->phone_conference;
    }

    /**
     * @param $a_stat
     */
    public function enablePhoneDialOut($a_stat)
    {
        $this->phone_dial_out = $a_stat;
    }

    /**
     * @return bool
     */
    public function isPhoneDialOutEnabled()
    {
        return $this->phone_dial_out;
    }

    /**
     * @param $a_stat
     */
    public function enablePhoneDialOutParticipants($a_stat)
    {
        $this->phone_dial_out_part = $a_stat;
    }

    /**
     * @return bool
     */
    public function isPhoneDialOutParticipantsEnabled()
    {
        return $this->phone_dial_out_part;
    }

    /**
     * @param bool $a_mobile_access
     */
    public function enableMobileAccess($a_mobile_access)
    {
        $this->mobile_access_enabled = $a_mobile_access;
    }

    /**
     * @return bool
     */
    public function isMobileAccessEnabled()
    {
        return $this->mobile_access_enabled;
    }

    /**
     * @param bool $a_session_recorder
     */
    public function enableSessionRecorder($a_session_recorder)
    {
        $this->session_recorder = $a_session_recorder;
    }

    /**
     * @return bool
     */
    public function isSessionRecorderEnabled()
    {
        return $this->session_recorder;
    }

    public function enableLearningProgress($a_stat)
    {
        $this->enable_learning_progress = $a_stat;
    }

    public function isLearningProgressEnabled()
    {
        return $this->enable_learning_progress;
    }

    /**
     * @param bool
     */
    public function setInspireSelectable($a_status)
    {
        $this->inspire = $a_status;
    }

    /**
     * Check if inspire is selectable
     */
    public function isInspireSelectable()
    {
        return $this->inspire;
    }

    /**
     * Get singelton instance
     * @return ilViteroSettings
     */
    public static function getInstance()
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance = new ilViteroSettings();
    }

    /**
     * Get direct link to group managment
     */
    public function getGroupFolderLink()
    {
        $group_url = str_replace('services', '', $this->getServerUrl());
        $group_url = ilUtil::removeTrailingPathSeparators($group_url);
        return $group_url . '/user/cms/groupfolder.htm';
    }

    public function getServerUrl()
    {
        return $this->url;
    }

    /**
     * Save settings
     */
    public function save()
    {
        $this->getStorage()->set('server', $this->getServerUrl());
        $this->getStorage()->set('admin', $this->getAdminUser());
        $this->getStorage()->set('pass', $this->getAdminPass());
        $this->getStorage()->set('customer', $this->getCustomer());
        $this->getStorage()->set('ldap', $this->isLdapUsed());
        $this->getStorage()->set('cafe', $this->isCafeEnabled());
        $this->getStorage()->set('content', $this->isContentAdministrationEnabled());
        $this->getStorage()->set('std_room', (int) $this->isStandardRoomEnabled());
        $this->getStorage()->set('webstart', $this->getWebstartUrl());
        $this->getStorage()->set('uprefix', $this->getUserPrefix());
        $this->getStorage()->set('grace_period_before', $this->getStandardGracePeriodBefore());
        $this->getStorage()->set('grace_period_after', $this->getStandardGracePeriodAfter());
        $this->getStorage()->set('avatar', (int) $this->isAvatarEnabled());
        $this->getStorage()->set('mtom_cert', $this->getMTOMCert());
        $this->getStorage()->set('phone_conference', (int) $this->isPhoneConferenceEnabled());
        $this->getStorage()->set('phone_dial_out', (int) $this->isPhoneDialOutEnabled());
        $this->getStorage()->set('phone_dial_out_participants', $this->isPhoneDialOutParticipantsEnabled());
        $this->getStorage()->set('mobile', (int) $this->isMobileAccessEnabled());
        $this->getStorage()->set('recorder', (int) $this->isSessionRecorderEnabled());
        $this->getStorage()->set('learning_progress', $this->isLearningProgressEnabled());
        $this->getStorage()->set('inspire', $this->isInspireSelectable());
        $this->getStorage()->set('file_handling_ilias', (int) $this->isFileHandlingIliasEnabled());
        $this->getStorage()->set('file_handling_vitero', (int) $this->isFileHandlingViteroEnabled());

    }

    public function getAdminUser()
    {
        return $this->admin;
    }

    public function getAdminPass()
    {
        return $this->pass;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($a_cust)
    {
        $this->customer = $a_cust;
    }

    public function isLdapUsed()
    {
        return $this->use_ldap;
    }

    public function isCafeEnabled()
    {
        return $this->enable_cafe;
    }

    /**
     * @return bool
     */
    public function isContentAdministrationEnabled()
    {
        return $this->isFileHandlingViteroEnabled() || $this->isFileHandlingIliasEnabled();
    }

    public function isStandardRoomEnabled()
    {
        return $this->enable_standard_room;
    }

    public function getWebstartUrl()
    {
        return $this->webstart;
    }

    public function getUserPrefix()
    {
        return $this->user_prefix;
    }

    public function setUserPrefix($a_prefix)
    {
        $this->user_prefix = $a_prefix;
    }

    public function getStandardGracePeriodBefore()
    {
        return $this->grace_period_before;
    }

    public function getStandardGracePeriodAfter()
    {
        return $this->grace_period_after;
    }

    public function isAvatarEnabled()
    {
        return (bool) $this->avatar;
    }

    public function getMTOMCert()
    {
        return $this->mtom_cert;
    }

    public function setMTOMCert($a_cert)
    {
        $this->mtom_cert = $a_cert;
    }

    /**
     * Check if vitero connection is configured
     * @return bool
     */
    public function isConfigured()
    {
        return $this->getCustomer() && $this->getAdminPass();
    }

    /**
     * @param bool $a_phone_enabled
     */
    public function enablePhoneOptions($a_phone_enabled)
    {
        $this->phone_enabled = $a_phone_enabled;
    }

    /**
     * @return bool
     */
    public function isFileHandlingIliasEnabled() : bool
    {
        return $this->file_handling_ilias_enabled;
    }

    /**
     * @param bool $file_handling_ilias_enabled
     */
    public function setFileHandlingIliasEnabled(bool $file_handling_ilias_enabled) : void
    {
        $this->file_handling_ilias_enabled = $file_handling_ilias_enabled;
    }

    /**
     * @return bool
     */
    public function isFileHandlingViteroEnabled() : bool
    {
        return $this->file_handling_vitero_enabled;
    }

    /**
     * @param bool $file_handling_vitero_enabled
     */
    public function setFileHandlingViteroEnabled(bool $file_handling_vitero_enabled) : void
    {
        $this->file_handling_vitero_enabled = $file_handling_vitero_enabled;
    }


    /**
     * @return bool
     */
    public function arePhoneOptionsEnabled()
    {
        return
            $this->isPhoneConferenceEnabled() ||
            $this->isPhoneDialOutEnabled() ||
            $this->isPhoneDialOutParticipantsEnabled();
    }

}

?>
