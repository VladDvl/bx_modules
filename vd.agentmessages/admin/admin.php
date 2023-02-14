<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/prolog.php');
require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/include/prolog_admin_after.php');

use VD\AgentMessages\Options;
use VD\AgentMessages\MessageAgent;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

IncludeModuleLangFile(__FILE__);
$title = Loc::GetMessage('vd.agentmessages_MODULE_NAME_SETTINGS');

$scheduleList = Options::getScheduleList();
$defaultSchedule = Options::getDefaultOptions();
$arRunningAgents = MessageAgent::getActiveAgents();

$arUserId = [];
$arDepartmentId = [];
if (!empty($scheduleList)) {
    foreach ($scheduleList as $key => $fields)
    {
        $users = $fields['users'];
        if (!empty($users)) {
            $arUserId = array_merge($arUserId, $users);
        }
        $departments = $fields['departments'];
        if (!empty($departments)) {
            $arDepartmentId = array_merge($arDepartmentId, $departments);
        }
    }
    $arUserId = array_unique($arUserId);
    $arDepartmentId = array_unique($arDepartmentId);
}

$arUserNames = [];
if (!empty($arUserId)) {
    foreach ($arUserId as $id)
    {
        $rs = \CUser::GetByID($id);
        if (!$rs) {
            continue;
        }
        $arUser = $rs->fetch();
        if (empty($arUser)) {
            continue;
        }
        $fio = '';
        $name = $arUser['NAME'];
        $lastName = $arUser['LAST_NAME'];
        if (!empty($name)) {
            $fio .= $name;
            if (!empty($lastName)) {
                $fio .= ' '.$lastName;
            }
        }
        $arUserNames[$id] = (!empty($fio)) ? $fio : $id;
    }
}
$arDepartmentNames = [];
if (!empty($arDepartmentId) && Loader::includeModule('intranet')) {
    $arDepartmentNames = \CIntranetUtils::GetDepartmentsData($arDepartmentId);
}

$data = [
    'settings' => [
        'controls' => [
            'buttonAdd' => [
                'name' => Loc::GetMessage('vd.agentmessages_BUTTON_ADD'),
                'func' => 'Add',
                'type' => 1,
            ],
        ],
        'buttons' => [
            'save' => [
                'name' => Loc::GetMessage('vd.agentmessages_BUTTON_SAVE'),
                'func' => 'Save',
                'type' => 1,
            ],
            'enable' => [
                'name' => Loc::GetMessage('vd.agentmessages_BUTTON_RUN'),
                'func' => 'EnableAgent',
                'type' => 1,
            ],
            'disable' => [
                'name' => Loc::GetMessage('vd.agentmessages_BUTTON_STOP'),
                'func' => 'DisableAgent',
                'type' => 2,
            ],
            'delete' => [
                'name' => Loc::GetMessage('vd.agentmessages_BUTTON_DELETE'),
                'func' => 'Delete',
                'type' => 2,
            ],
        ],
        'agent_messages' => [
            'enabled' => Loc::GetMessage('vd.agentmessages_MSG_ENABLED'),
            'disabled' => Loc::GetMessage('vd.agentmessages_MSG_DISABLED'),
            'next_exec' => Loc::GetMessage('vd.agentmessages_MSG_NEXT_EXECUTION'),
        ],
        'fields' => [
            [
                'id' => 'start_time',
                'title' => Loc::GetMessage('vd.agentmessages_FIELD_START_TIME'),
                'type' => 'text',
                'required' => true,
                'width' => '200',
            ],
            [
                'id' => 'period',
                'title' => Loc::GetMessage('vd.agentmessages_FIELD_PERIOD_DAYS'),
                'type' => 'number',
                'required' => true,
                'width' => '200',
                'min' => '1',
                'step' => '1',
            ],
            [
                'id' => 'period_type',
                'title' => Loc::GetMessage('vd.agentmessages_FIELD_PERIOD_TYPE'),
                'type' => 'hidden',
                'required' => false,
            ],
            [
                'id' => 'time',
                'title' => Loc::GetMessage('vd.agentmessages_FIELD_TIME'),
                'type' => 'text',
                'required' => true,
                'width' => '200',
            ],
            [
                'id' => 'users-departments',
                'title' => Loc::GetMessage('vd.agentmessages_FIELD_USERS_DEPARTMENTS'),
                'type' => 'entity-selector',
                'required' => true,
            ],
            [
                'id' => 'departments',
                'title' => Loc::GetMessage('vd.agentmessages_FIELD_DEPARTMENTS'),
                'type' => 'hidden',
                'required' => false,
            ],
            [
                'id' => 'users',
                'title' => Loc::GetMessage('vd.agentmessages_FIELD_USERS'),
                'type' => 'hidden',
                'required' => false,
            ],
            [
                'id' => 'message',
                'title' => Loc::GetMessage('vd.agentmessages_FIELD_MESSAGE'),
                'type' => 'textarea',
                'required' => true,
            ],
        ],
    ],
    'schedule_list' => (count($scheduleList) > 0) ? $scheduleList : '',
    'default_schedule' => $defaultSchedule,
    'agent_list' => (count($arRunningAgents) > 0) ? $arRunningAgents : '',
    'userNames' => $arUserNames,
    'departmentNames' => $arDepartmentNames,
];
$data = Json::encode($data);

Extension::load(['ui.vue', 'ui.entity-selector']);
global $APPLICATION;
$APPLICATION->SetTitle($title);
$APPLICATION->SetAdditionalCSS('/local/modules/vd.agentmessages/assets/admin.css');
$APPLICATION->AddHeadScript('/local/modules/vd.agentmessages/assets/admin.js');
?>
<div id="schedule-list-app"></div>
<script>
BX.ready(function () {
    var settings = new Agentmessages.Settings(<?=\CUtil::PhpToJSObject($data);?>);
    settings.initialize();
});
</script>
<?php require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/include/epilog_admin.php');
