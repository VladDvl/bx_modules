<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$postList = $request->getPostList();

$entity = $postList->getRaw('entity');

$result = false;
if (!empty($entity)) {
    global $USER;
    $userId = $USER->getId();
    $isAdmin = $USER->isAdmin();

    $optionsData = \VD\CrmSection\Options::getSections($entity);

    $result = [
        'userId' => $userId,
        'isAdmin' => $isAdmin,
        'optionsData' => (!empty($optionsData)) ? $optionsData : [],
        'entity' => $entity,
    ];
}
echo \Bitrix\Main\Web\Json::encode($result);