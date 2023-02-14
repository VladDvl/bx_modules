<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

class Vd_Agentmessages extends \CModule
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
        $this->MODULE_ID = 'vd.agentmessages';
        $this->MODULE_NAME = Loc::GetMessage('vd.agentmessages_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::GetMessage('vd.agentmessages_MODULE_DESC');
        $this->PARTNER_NAME = Loc::GetMessage('vd.agentmessages_PARTNER_NAME');
        $this->PARTNER_URI = Loc::GetMessage('vd.agentmessages_PARTNER_URI');
    }

    public function installEvents()
    {
        RegisterModule($this->MODULE_ID);
        RegisterModuleDependences('main', 'OnBuildGlobalMenu', $this->MODULE_ID, 'VD\AgentMessages\Handlers', 'OnBuildGlobalMenu');
    }

    public function uninstallEvents()
    {
        UnRegisterModule($this->MODULE_ID);
        UnRegisterModuleDependences('main', 'OnBuildGlobalMenu', $this->MODULE_ID, 'VD\AgentMessages\Handlers', 'OnBuildGlobalMenu');
    }

    public function installFiles()
    {
        $path = preg_replace('#install$#', 'admin', dirname(__FILE__));
        if ($path && file_exists($path)) {
            if ($dir = opendir($path)) {
                while (false !== $item = readdir($dir))
                {
                    if (in_array($item, ['.', '..', 'menu.php'])) continue;

                    if (!file_exists($file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$this->MODULE_ID.'_'.$item)) {
                        $str = '<?php require(\''.$_SERVER['DOCUMENT_ROOT'].'/local/modules/'.$this->MODULE_ID.'/admin/'.$item.'\');?>';
                        file_put_contents($file, $str);
                    }
                }
                closedir($dir);
            }
        }
    }

    public function uninstallFiles()
    {
        if (is_dir($path = $_SERVER['DOCUMENT_ROOT'].'/local/modules/'.$this->MODULE_ID.'/admin')) {
            if ($dir = opendir($path)) {
                while (false !== $item = readdir($dir))
                {
                    if ($item == '..' || $item == '.') continue;

                    $itemPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$this->MODULE_ID.'_'.$item;
                    if (file_exists($itemPath)) {
                        unlink($itemPath);
                    }
                }
                closedir($dir);
            }
        }
    }

    public function doInstall()
    {
        $this->installFiles();
        $this->installEvents();
    }

    public function doUninstall()
    {
        $this->uninstallFiles();
        $this->uninstallEvents();
    }
}
