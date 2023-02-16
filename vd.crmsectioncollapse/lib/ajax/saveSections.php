<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$postList = $request->getPostList();

$sectionsData = $postList->getRaw('sectionsData');
$forAll = $postList->getRaw('forAll');
$entity = $postList->getRaw('entity');
$userId = intval($postList->getRaw('userId'));

$result = [];
if ($userId > 0 && !empty($sectionsData) && !empty($entity)) {
    $saveData = [];
    $optionsData = \VD\CrmSection\Options::getSections($entity);

    $users = [];
    if (!empty($optionsData['users'])) {
        $users = $optionsData['users'];
    }
    $users[] = $userId;
    $users = array_unique($users);

    $saveData['sections'] = $sectionsData;
    $saveData['forAll'] = $forAll;
    $saveData['users'] = $users;

    \VD\CrmSection\Options::setSections($saveData, $entity);
    $result['result'] = $saveData;
}
echo \Bitrix\Main\Web\Json::encode($result);