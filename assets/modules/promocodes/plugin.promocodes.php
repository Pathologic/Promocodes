<?php
include_once MODX_BASE_PATH . 'assets/modules/promocodes/autoload.php';
$plugin = new Pathologic\Commerce\Promocodes\Plugin($modx, $params);
$event = $modx->event->name;
if (method_exists($plugin, $event)) {
    $plugin->$event();
}
