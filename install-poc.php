<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

require_once 'admin.php';
require_once 'system/installer/controllers/wizard.php';
class EE_CLI_Installer extends Wizard {

}

$installer = new EE_CLI_Installer();
$installer->do_install();
