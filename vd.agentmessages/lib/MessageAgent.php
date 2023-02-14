<?php
namespace VD\AgentMessages;

use Bitrix\Main\Loader;

class MessageAgent
{
    public static function getAgentList()
    {
        $arFilter = [
            'MODULE_ID' => Options::MODULE_ID,
            'NAME' => '%AgentMessages\MessageAgent::run%'
        ];

        $dbAgents = \CAgent::GetList(['ID' => 'ASC'], $arFilter);

        $arAgentList = [];
        while ($res = $dbAgents->fetch())
        {
            $arAgentList[] = $res;
        }
        return $arAgentList;
    }

    public static function getActiveAgents()
    {
        $arFilter = [
            'MODULE_ID' => Options::MODULE_ID,
            'NAME' => '%AgentMessages\MessageAgent::run%',
            'ACTIVE' => 'Y',
        ];

        $dbAgents = \CAgent::GetList(['ID' => 'ASC'], $arFilter);

        $scheduleList = Options::getScheduleList();

        $result = [];
        while ($res = $dbAgents->fetch())
        {
            $pattern = '#run\(\'(.+)\',\d+\)#';
            if (preg_match($pattern, $res['NAME'], $matches)) {

                $agentKey = $matches[1];
                if (!empty($agentKey)) {

                    if (empty($scheduleList[$agentKey])) {
                        self::deleteAgent($res['NAME']);
                        continue;
                    }

                    $result[$agentKey] = [
                        'ACTIVE' => $res['ACTIVE'],
                        'NEXT_EXEC' => $res['NEXT_EXEC'],
                        'ID' => $res['ID'],
                    ];
                }
            }
        }
        return $result;
    }

    public static function deleteAgent($name)
    {
        \CAgent::RemoveAgent($name, Options::MODULE_ID);
    }

    public static function run($scheduleId, $i=1)
    {
        $scheduleList = Options::getScheduleList();
        if (empty($scheduleList) || !array_key_exists($scheduleId, $scheduleList)) return '';
        $schedule = $scheduleList[$scheduleId];

        $departments = $schedule['departments'];
        $arDepartmentUsers = [];
        if (!empty($departments)) {
            $arDepartmentUsers = self::getUsersFromDepartments($departments);
        }

        $optionUsers = $schedule['users'];
        if (!empty($optionUsers)) {
            $optionUsers = array_map('intval', $optionUsers);
        }

        $arUsers = [];
        if (!empty($arDepartmentUsers)) $arUsers = array_merge($arUsers, $arDepartmentUsers);
        if (!empty($optionUsers)) $arUsers = array_merge($arUsers, $optionUsers);
        if (!empty($arUsers)) $arUsers = array_unique($arUsers);

        $message = $schedule['message'];

        $period = '';
        $periodNumber = $schedule['period'];
        $periodType = $schedule['period_type'];
        if (!empty($periodNumber) && !empty($periodType)) {
            $period = '+'.$periodNumber.' '.$periodType;
        }

        $optionTimeStr = $schedule['time'];
        if (empty($optionTimeStr)) $optionTimeStr = self::getDefaultTimeStr();

        if (!empty($arUsers) && !empty($message) && !empty($period)) {

            try {
                self::sendToUsers($arUsers, $message);

            } catch (\Exception $e) {
                Handlers::_log([
                    'data' => [
                        'arUsers' => $arUsers,
                        'message' => $message,
                        'scheduleId' => $scheduleId,
                    ],
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                ]);
                return '';
            }

            $startTime = date('d.m.Y '.$optionTimeStr, strtotime($period));
            $i = intval($i);
            $i++;
            $newAgentId = self::addAgent($scheduleId, $startTime, $i);
        }
        return '';
    }

    public static function addAgent($scheduleId, $startTime, $i=1)
    {
        if (empty($scheduleId)) return false;

        $agentId = \CAgent::AddAgent(
            '\VD\AgentMessages\MessageAgent::run(\''.$scheduleId.'\','.intval($i).');',
            Options::MODULE_ID,
            'N',
            86400,
            $startTime,
            'Y',
            $startTime,
            0
        );
        return $agentId;
    }

    private static function getUsersFromDepartments($arDepartments)
    {
        $arDepartmentUsers = [];
        if (!empty($arDepartments)) {
            foreach ($arDepartments as $departmentId)
            {
                $departmentId = intval($departmentId);
                if (!empty($departmentId)) {

                    $userRes = \CUser::GetList(
                        $by = 'id',
                        $order = 'asc',
                        ['UF_DEPARTMENT' => $departmentId],
                        ['FIELDS ' => ['ID']]
                    );

                    while ($user = $userRes->Fetch())
                    {
                        $arDepartmentUsers[] = intval($user['ID']);
                    }
                }
            }
        }
        if (!empty($arDepartmentUsers)) $arDepartmentUsers = array_unique($arDepartmentUsers);
        return $arDepartmentUsers;
    }

    private static function sendToUsers($arUsers, $msgText)
    {
        if (is_array($arUsers)) {
            foreach ($arUsers as $userId)
            {
                self::sendMessage($userId, $msgText);
            }
        }
    }

    private static function sendMessage($toUserId, $msgText)
    {
        if (empty($toUserId) || empty($msgText)) return;

        Loader::includeModule('im');
        $fromUserId = self::getDefaultUserId();

        $arMessageFields = [
            'FROM_USER_ID' => $fromUserId,
            'TO_USER_ID' => $toUserId,
            'NOTIFY_TYPE' => IM_NOTIFY_FROM,
            'NOTIFY_MODULE' => Options::MODULE_ID,
            'NOTIFY_TAG' => '',
            'NOTIFY_MESSAGE' => $msgText,
        ];

        \CIMNotify::Add($arMessageFields);
    }

    public static function getDefaultTimeStr()
    {
        return 'H:i:s';
    }

    public static function getDefaultUserId()
    {
        return 1;
    }
}
