<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Exceptions/classes/class.ilException.php';

/**
 * Soap connector exceptrion
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroConnectorException.php 31404 2011-11-03 14:24:50Z smeyer $
 */
class ilViteroConnectorException extends ilException
{

    public function getViteroMessage() : string
    {
        if ((int) $this->getCode() > 0) {
            $error_name = 'err_soap_' . (int) $this->getCode();
            $plugin = ilViteroPlugin::getInstance();

            if ($plugin->txt($error_name) === '-' . $plugin->getPrefix() . '_' . $error_name . '-') {
                return sprintf($plugin->txt('err_soap_generic'), (int) $this->getCode());
            } else {
                return ilViteroPlugin::getInstance()->txt($error_name);
            }
        }

        return $this->getMessage();
    }
}

?>
