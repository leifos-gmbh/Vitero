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

    public function getViteroMessage()
    {
        if ((int) $this->getCode() > 0) {
            return ilViteroPlugin::getInstance()->txt('err_soap_' . (int) $this->getCode());
        }
        return $this->getMessage();
    }
}

?>
