<?php

namespace Pathologic\Commerce\Promocodes;

class Module
{
    protected $modx;
    protected $params = [];
    protected $DLTemplate;

    public function __construct($modx)
    {
        $this->modx = $modx;
        $this->params = $modx->event->params;
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        $model = new Model($modx);
        $model->createTable();
    }

    public function prerender()
    {
        $tpl = MODX_BASE_PATH . 'assets/modules/promocodes/tpl/module.tpl';
        $output = '';
        if (is_readable($tpl)) {
            $output = file_get_contents($tpl);
        }

        return $output;
    }

    public function render()
    {
        $output = $this->prerender();
        $ph = [
            'connector'   => $this->modx->config['site_url'] . 'assets/modules/promocodes/ajax.php',
            'site_url'    => $this->modx->config['site_url'],
            'theme'       => $this->modx->config['manager_theme'],
            'manager_url' => MODX_MANAGER_URL,
            'pattern'     => ci()->promocodes->getSetting('pattern', '[A-Z0-9]{10}')
        ];
        $output = $this->DLTemplate->parseChunk('@CODE:' . $output, $ph);

        return $output;
    }
}
