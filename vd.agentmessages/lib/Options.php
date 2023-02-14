<?php
namespace VD\AgentMessages;

use Bitrix\Main\Config\Option;

class Options
{
    const MODULE_ID = 'vd.agentmessages';

    public static function getScheduleList()
    {
        $scheduleList = self::getOption(self::getDefaultOptionsName());
        if (!empty($scheduleList)) return $scheduleList;
        return [];
    }

    public static function setScheduleList($data)
    {
        self::setOption(self::getDefaultOptionsName(), $data);
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
        Option::delete(self::MODULE_ID, $name);
    }

    public static function getDefaultOptions()
    {
        return [
            'id' => 'default',
            'departments' => [],
            'users' => [],
            'period' => '15',
            'period_type' => 'days',
            'start_time' => date('d.m.Y 08:00:00'),
            'time' => '08:00:00',
            'message' => '',
        ];
    }

    public static function getDefaultOptionsName()
    {
        return 'schedule_messages_list';
    }
}
