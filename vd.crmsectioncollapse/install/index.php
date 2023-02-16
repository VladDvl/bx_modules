<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

class Vd_Crmsectioncollapse extends \CModule
{
    var $MODULE_ID,
        $MODULE_VERSION,
        $MODULE_VERSION_DATE,
        $MODULE_NAME,
        $MODULE_DESCRIPTION,
        $PARTNER_NAME,
        $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__.'/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_ID = 'vd.crmsectioncollapse';
        $this->MODULE_NAME = Loc::GetMessage('vd.crmsectioncollapse_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::GetMessage('vd.crmsectioncollapse_MODULE_DESC');
        $this->PARTNER_NAME = Loc::GetMessage('vd.crmsectioncollapse_PARTNER_NAME');
        $this->PARTNER_URI = Loc::GetMessage('vd.crmsectioncollapse_PARTNER_URI');
    }

    public function doInstall()
    {
        $this->installEvents();
    }

    public function doUninstall()
    {
        $this->uninstallEvents();
    }

    public function installEvents()
    {
        RegisterModule($this->MODULE_ID);
        RegisterModuleDependences('main', 'OnBeforeProlog', $this->MODULE_ID, 'VD\CrmSection\Handler', 'OnBeforePrologHandler');
    }

    public function uninstallEvents()
    {
        UnRegisterModule($this->MODULE_ID);
        UnRegisterModuleDependences('main', 'OnBeforeProlog', $this->MODULE_ID, 'VD\CrmSection\Handler', 'OnBeforePrologHandler');
    }
}
