<?php
use Bitrix\Main\Loader;

$arClasses = [
    'VD\CrmSection\Options' => 'lib/Options.php',
    'VD\CrmSection\Handler' => 'lib/Handler.php',
];

Loader::registerAutoLoadClasses('vd.crmsectioncollapse', $arClasses);
