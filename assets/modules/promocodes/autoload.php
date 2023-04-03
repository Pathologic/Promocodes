<?php

if (file_exists(MODX_BASE_PATH . 'assets/snippets/FormLister/__autoload.php')) {
    require_once MODX_BASE_PATH . 'assets/snippets/FormLister/__autoload.php';
}

spl_autoload_register(function ($class) {
    static $classes = null;

    if ($classes === null) {
        $classes = [
            'site_contentDocLister' => 'assets/snippets/DocLister/core/controller/site_content.php',
            'onetableDocLister'     => 'assets/snippets/DocLister/core/controller/onetable.php',
            'DocLister'             => 'assets/snippets/DocLister/core/DocLister.abstract.php',
        ];
    }

    if (isset($classes[$class])) {
        if (is_array($classes[$class])) {
            foreach ($classes[$class] as $classFile) {
                if (is_readable(MODX_BASE_PATH . $classFile)) {
                    require MODX_BASE_PATH . $classFile;
                    return;
                }
            }
        } else {
            require MODX_BASE_PATH . $classes[$class];
        }

        return;
    }

    if (strpos($class, 'Pathologic\\Commerce\\Promocodes\\') === 0) {
        $parts = str_replace('Pathologic\\Commerce\\Promocodes\\', '', $class);

        $filename = __DIR__ . '/src/' . str_replace('\\', '/', $parts) . '.php';

        if (is_readable($filename)) {
            require $filename;
        }
    }
}, true);
