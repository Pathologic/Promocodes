<?php
if (IN_MANAGER_MODE != "true" || empty($modx) || !($modx instanceof \DocumentParser)) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('exec_module')) {
    header("location: " . $modx->getManagerPath() . "?a=106");
}
include_once 'autoload.php';
if (!isset($modx->event->params) || !is_array($modx->event->params)) {
    $modx->event->params = [];
}
$module = new Pathologic\Commerce\Promocodes\Module($modx);
echo($module->render());
