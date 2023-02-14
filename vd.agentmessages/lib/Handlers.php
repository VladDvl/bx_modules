<?php
namespace VD\AgentMessages;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Diag\Debug;

IncludeModuleLangFile(__FILE__);

class Handlers
{
    public function OnBuildGlobalMenu(&$arGlobalMenu, &$arModuleMenu)
    {
        if ($GLOBALS['APPLICATION']->GetGroupRight('main') < 'R') return;

        $MODULE_ID = Options::MODULE_ID;
        $arMenu = [
            'parent_menu' => 'global_menu_settings',
            'section' => $MODULE_ID,
            'sort' => 50,
            'text' => Loc::GetMessage('vd.agentmessages_MODULE_NAME'),
            'title' => '',
            'icon' => '',
            'page_icon' => '',
            'items_id' => $MODULE_ID.'_items',
            'more_url' => [],
            'items' => [],
        ];

        $path = preg_replace('#lib$#', 'admin', dirname(__FILE__));
        if ($path && is_dir($path)) {
            if ($dir = opendir($path)) {

                $arFiles = [];
                while (false !== $item = readdir($dir))
                {
                    if (in_array($item, ['.','..','menu.php'])) continue;
                    $arFiles[] = $item;
                }
                closedir($dir);
                sort($arFiles);

                foreach($arFiles as $item)
                {
                    $arMenu['items'][] = [
                        'text' => Loc::GetMessage('vd.agentmessages_SETTINGS'),
                        'url' => $MODULE_ID . '_' . $item,
                        'module_id' => $MODULE_ID,
                        'title' => '',
                    ];
                }

            }
        }
        $arModuleMenu[] = $arMenu;
    }

    public static function _log($data, $fName='')
    {
        $path = '/local/modules/'.Options::MODULE_ID.'/log/';

        $isWritable = is_writable($_SERVER['DOCUMENT_ROOT'].$path);
        if (!$isWritable) {
            $path = '/local/';
        }
        if (empty($fName)) {
            $fName = '_log_AgentMessages_'.date('d.m.Y');
        }
        $path .= $fName.'.log';
        Debug::writeToFile($data, date('d.m.Y H:i:s'), $path);
    }
}
