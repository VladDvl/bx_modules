<?php
namespace VD\CrmSection;

use Bitrix\Main\Web\Json;

class Handler
{
    public static function OnBeforePrologHandler()
    {
        global $APPLICATION;
        $arEntityStr = Options::getMultiSelectOptionValues('CRM_PAGE_ENTITY_STR');

        foreach ($arEntityStr as $entityStr)
        {
            $loadExt = mb_strpos($APPLICATION->sDocPath2, '/crm/'.$entityStr.'/details/') !== false;
            if ($loadExt) {
                \CJSCore::RegisterExt(
                    'vd_crm_card_section_collapse',
                    [
                        'js' => '/local/modules/vd.crmsectioncollapse/assets/card.js',
                        'css' => '/local/modules/vd.crmsectioncollapse/assets/card.css',
                        'rel' => ['ajax'],
                    ]
                );
                \CJSCore::Init('vd_crm_card_section_collapse');
                break;
            }
        }
    }
}
