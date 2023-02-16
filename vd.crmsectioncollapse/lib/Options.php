<?php
namespace VD\CrmSection;

use Bitrix\Main\Config\Option;

class Options
{
    const MODULE_ID = 'vd.crmsectioncollapse';

    public static function getMultiSelectOptionValues($optionName)
    {
        $multiSelectValues = Option::get(self::MODULE_ID, $optionName);
        return array_filter(explode(',', $multiSelectValues), function ($multiSelectValue) {
            return !empty(trim($multiSelectValue));
        });
    }

    public static function getSections($entity)
    {
        if (!empty($entity)) {
            $name = 'section_show_'.$entity;
            $sectionsData = self::getOption($name);
            if (!empty($sectionsData)) return $sectionsData;
        }
        return [];
    }

    public static function setSections($data, $entity)
    {
        if (!empty($data) && !empty($entity)) {
            $name = 'section_show_'.$entity;
            self::setOption($name, $data);
        }
    }

    private static function getOption($name)
    {
        $optionsStr =  Option::get(self::MODULE_ID, $name, '');
        if (!empty($optionsStr)) return unserialize($optionsStr);
        return '';
    }

    private static function setOption($name, $optionData)
    {
        Option::set(self::MODULE_ID, $name, serialize($optionData));
    }

    private static function removeOptions($name)
    {
        Option::delete(self::MODULE_ID, ['name' => $name]);
    }
}
