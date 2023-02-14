<?php
namespace VD\AgentMessages;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);

class AdminController extends Controller
{
    public static function checkFields($fields)
    {
        $errorMessage = '';
        if (empty($fields['id'])) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_ID');

        } else if (empty($fields['departments']) && empty($fields['users'])) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_USERS');

        } else if (empty($fields['period'])) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_PERIOD');

        } else if (empty($fields['period_type'])) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_PERIOD_TYPE');

        } else if (empty($fields['start_time'])) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_START_TIME');

        } else if (empty($fields['time'])) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_TIME');

        } else if (empty($fields['message'])) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_MESSAGE');
        }
        return $errorMessage;
    }

    private static function save($scheduleId, $fields)
    {
        $scheduleList = Options::getScheduleList();

        if (!empty($fields['message'])) {
            $fields['message'] = htmlspecialcharsEx($fields['message']);
        }

        $scheduleList[$scheduleId] = $fields;
        Options::setScheduleList($scheduleList);
        return $scheduleList;
    }

    private static function removeAgent($scheduleId)
    {
        $result = false;
        $arAgentsName = self::getCreatedAgentsNameBySchedule($scheduleId);
        if (!empty($arAgentsName)) {
            foreach ($arAgentsName as $name)
            {
                MessageAgent::deleteAgent($name);
                $result = true;
            }
        }
        return $result;
    }

    private static function getCreatedAgentsNameBySchedule($scheduleId)
    {
        $arAgentName = [];
        $arAgentList = MessageAgent::getAgentList();
        if (!empty($arAgentList)) {

            $pattern = '#run\(\''.$scheduleId.'\',\d+\)#';
            foreach ($arAgentList as $agent)
            {
                if (preg_match($pattern, $agent['NAME'], $matches)) {
                    $arAgentName[] = $agent['NAME'];
                }
            }
        }
        return $arAgentName;
    }

    public static function saveAction($data = [])
    {
        $scheduleId = $data['scheduleId'];
        $fields = $data['fields'];

        $result = ['result' => false];
        $errorMessage = '';
        if (empty($scheduleId)) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_ID');
        } else {
            $errorMessage = self::checkFields($fields);
        }
        if (!empty($errorMessage)) {
            $result['errorMessage'] = $errorMessage;
            return $result;
        }

        $result['scheduleList'] = self::save($scheduleId, $fields);
        $result['result'] = true;
        return $result;
    }

    public static function deleteAction($data = [])
    {
        $scheduleId = $data['scheduleId'];

        $result = ['result' => false];
        $errorMessage = '';
        if (empty($scheduleId)) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_ID');
        }
        if (!empty($errorMessage)) {
            $result['errorMessage'] = $errorMessage;
            return $result;
        }

        self::removeAgent($scheduleId);

        $scheduleList = Options::getScheduleList();
        if (!empty($scheduleList)) {

            if (array_key_exists($scheduleId, $scheduleList)) {

                unset($scheduleList[$scheduleId]);
                Options::setScheduleList($scheduleList);
                $result['scheduleList'] = $scheduleList;
                $result['result'] = true;
            }
        }
        return $result;
    }

    public static function enableAgentAction($data = [])
    {
        $scheduleId = $data['scheduleId'];
        $fields = $data['fields'];

        $result = ['result' => false];
        $errorMessage = '';
        if (empty($scheduleId)) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_ID');
        } else {
            $errorMessage = self::checkFields($fields);
        }
        if (!empty($errorMessage)) {
            $result['errorMessage'] = $errorMessage;
            return $result;
        }

        self::save($scheduleId, $fields);

        $scheduleList = Options::getScheduleList();
        if (array_key_exists($scheduleId, $scheduleList)) {

            self::removeAgent($scheduleId);

            $startTime = $fields['start_time'];
            if (empty($startTime)) {
                $start = $fields['time'];
                $startTime = date('d.m.Y '.$start);
            }
            $agentId = MessageAgent::addAgent($scheduleId, $startTime);
            $result['scheduleList'] = $scheduleList;
            $result['agentId'] = $agentId;
            $result['result'] = true;
        }
        return $result;
    }

    public static function disableAgentAction($data = [])
    {
        $scheduleId = $data['scheduleId'];

        $result = ['result' => false];
        $errorMessage = '';
        if (empty($scheduleId)) {
            $errorMessage = Loc::GetMessage('vd.agentmessages_ERROR_EMPTY_ID');
        }
        if (!empty($errorMessage)) {
            $result['errorMessage'] = $errorMessage;
            return $result;
        }

        $result['result'] = self::removeAgent($scheduleId);
        return $result;
    }
}
