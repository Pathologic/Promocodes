<?php

use Pathologic\Commerce\Promocodes\Controllers\Controller;

define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', 'true');

include_once(__DIR__."/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
if(!isset($_SESSION['mgrValidated'])){
    $modx->sendErrorPage();
}
include_once MODX_BASE_PATH . 'assets/modules/promocodes/autoload.php';
$modx->invokeEvent('OnManagerPageInit');

$mode = (isset($_REQUEST['mode']) && is_scalar($_REQUEST['mode'])) ? $_REQUEST['mode'] : '';

if(strpos($mode, 'links/') === 0) {
    $mode = str_replace('links/', '', $mode);
    $controller = new \Pathologic\Commerce\Promocodes\Controllers\Links($modx);
} elseif(strpos($mode, 'export/') === 0) {
    $mode = str_replace('export/', '', $mode);
    $controller = new \Pathologic\Commerce\Promocodes\Controllers\Export($modx);
}else {
    $controller = new Controller($modx);
}
if (!empty($mode) && method_exists($controller, $mode)) {
    $out = call_user_func_array([$controller, $mode], []);
}else{
    $out = call_user_func_array([$controller, 'list'], []);
}

echo is_array($out) ? json_encode($out) : $out;
exit;
