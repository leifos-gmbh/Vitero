<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroGroupSoapConnector.php 33166 2012-02-14 13:49:39Z smeyer $
 */
class ilViteroGroupSoapConnector extends ilViteroSoapConnector
{
    const MEMBER_ROLE = 0;
    const ADMIN_ROLE = 2;

    const WSDL_NAME = 'group.wsdl';

    protected function getWsdlName()
    {
        return self::WSDL_NAME;
    }

    /**
     * Create Group
     * @param ilViteroGroupSoap $group
     * @throws ilViteroConnectorException $e
     */
    public function create(ilViteroGroupSoap $group)
    {
        try {

            $this->initClient();

            // Wrap into single group object
            $ogroup        = new stdClass();
            $ogroup->group = $group;
            $ret           = $this->getClient()->createGroup($ogroup);

            return $ret->groupid;
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Create vitero group failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }
    }

    /**
     * Delete a vitero group. Typically called after deletion of ILIAS objects
     * @param ilViteroGroup $group
     */
    public function delete(ilViteroGroupSoap $group)
    {
        try {

            $this->initClient();
            $this->getClient()->deleteGroup($group);
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Delete vitero group failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }
    }

    public function update(ilViteroGroupSoap $group)
    {
        try {
            $this->initClient();

            $envgroup        = new stdClass();
            $envgroup->group = $group;
            $this->getClient()->updateGroup($envgroup);
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Update vitero group failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }
    }

    public function addUserToGroup($a_vgroup_id, $a_vuser_id)
    {
        try {

            $this->initClient();

            $aug          = new stdClass();
            $aug->groupid = $a_vgroup_id;
            $aug->userid  = $a_vuser_id;

            $this->getClient()->addUserToGroup($aug);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Add user to group failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }

    }

    /**
     * Change group role of user
     * @param int $a_vgroup_id
     * @param int $a_vuser_id
     * @param int $a_vgroup_role
     */
    public function changeGroupRole($a_vgroup_id, $a_vuser_id, $a_vgroup_role)
    {
        try {

            $this->initClient();

            $aug          = new stdClass();
            $aug->groupid = $a_vgroup_id;
            $aug->userid  = $a_vuser_id;
            $aug->role    = $a_vgroup_role;

            $this->getClient()->changeGroupRole($aug);
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Change group role failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }
    }

    /**
     * Update enabled status
     * @param <type> $users
     * @param <type> $group_id
     * @param <type> $status
     */
    public function updateEnabledStatusForUsers($users, $group_id, $status)
    {
        $mapping = new ilViteroUserMapping();

        foreach ($users as $usr_id) {
            try {

                $vuser = $mapping->getVUserId($usr_id);
                if ($vuser) {
                    $this->changeEnabled($vuser, $group_id, $status);
                }
            } catch (Exception $e) {
                $GLOBALS['ilLog']->write(__METHOD__ . ': Update status failed with message ' . $e);
            }
        }
    }

    /**
     * Change enabled status
     * @param int  $usr_id
     * @param int  $group_id
     * @param bool $status
     */
    public function changeEnabled($usr_id, $group_id, $status)
    {
        try {

            $this->initClient();

            $aug          = new stdClass();
            $aug->groupid = $group_id;
            $aug->userid  = $usr_id;
            $aug->enabled = (bool) $status;
            $this->getClient()->changeEnabled($aug);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Change enabled status failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }

    }

    public function getGroupListByCustomer($customer_id)
    {
        try {
            $this->initClient();

            $request             = new stdClass();
            $request->customerid = $customer_id;

            return $this->getClient()->getGroupListByCustomer($request);
        } catch (SoapFault $e) {
            $code = $this->parseErrorCode($e);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Change group role failed with message code: ' . $code);
            $GLOBALS['ilLog']->write(__METHOD__ . ': Last request: ' . $this->getClient()->__getLastRequest());
            throw new ilViteroConnectorException($e->getMessage(), $code);
        }
    }
}

?>