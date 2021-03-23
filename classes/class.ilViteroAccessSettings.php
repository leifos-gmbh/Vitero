<?php

/**
 * Global vitero access settings
 * @author Marvin Barz <barz@leifos.com>

 */
class ilViteroAccessSettings
{
    const APPOINTMENT_CREATE = 1;
    const APPOINTMENT_EDIT_CREATE = 2;

    private static $instance = null;

    private $storage = null;
    private $enable_adv_access_rules = false;
    private $white_list = array();
    private $appointment_right = self::APPOINTMENT_CREATE;

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
    protected function read() : void
    {
        $this->EnableAdvancedAccessRules($this->getStorage()->get('adv_access_rules', $this->isAdvancedAccessRulesEnabled()));
        $white_list = (!empty($this->getStorage()->get('whitelist'))) ? explode(",",$this->getStorage()->get('whitelist')) : $this->getWhiteList();
        $this->setWhiteList($white_list);
        $this->setAppointmentRight($this->getStorage()->get('appointment_right', $this->getAppointmentRight()));
    }

    /**
     * Get singelton instance
     * @return ilViteroAccessSettings
     */
    public static function getInstance() : ilViteroAccessSettings
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance = new ilViteroAccessSettings();
    }

    /**
     * Get storage
     * @return ilSetting
     */
    public function getStorage() : ilSetting
    {
        return $this->storage;
    }

    /**
     * Save settings
     */
    public function save() : void
    {
        $this->getStorage()->set('adv_access_rules', $this->isAdvancedAccessRulesEnabled());
        $this->getStorage()->set('whitelist', implode(",", $this->getWhiteList()));
        $this->getStorage()->set('appointment_right', $this->getAppointmentRight());

    }

    /**
     * @return bool
     */
    public function isAdvancedAccessRulesEnabled() : bool
    {
        return $this->enable_adv_access_rules;
    }

    /**
     * @param bool $enable_adv_access_rules
     */
    public function enableAdvancedAccessRules(bool $enable_adv_access_rules) : void
    {
        $this->enable_adv_access_rules = $enable_adv_access_rules;
    }

    /**
     * @param bool $as_names
     * @return array
     */
    public function getWhiteList(bool $as_names = false) : array
    {
        if ($as_names === true) {
               $names = [];
               foreach ($this->white_list as $user_id) {
                   $name = ilObjUser::_lookupName($user_id);
                   $names[] = $name['login'];
               }
               return $names;
        }

        return $this->white_list;
    }

    /**
     * @param array $white_list
     */
    public function setWhiteList(array $white_list) : void
    {
        $this->white_list = $white_list;
    }

    /**
     * @return int
     */
    public function getAppointmentRight() : int
    {
        return $this->appointment_right;
    }

    /**
     * @param int $appointment_right
     */
    public function setAppointmentRight(int $appointment_right) : void
    {
        if($appointment_right === self::APPOINTMENT_CREATE || $appointment_right === self::APPOINTMENT_EDIT_CREATE) {
            $this->appointment_right = $appointment_right;
        }
    }

    public function isUserWhitelisted(int $user_id) : bool
    {
        return in_array($user_id, $this->getWhiteList());
    }

}

?>
