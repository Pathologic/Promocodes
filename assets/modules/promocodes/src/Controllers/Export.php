<?php

namespace Pathologic\Commerce\Promocodes\Controllers;

use Pathologic\Commerce\Promocodes\DocLister\FiltersTrait;

class Export
{
    use FiltersTrait;

    protected $modx;
    public array $fields = [
        'id'            => 'IO',
        'promocode'     => 'Промокод',
        'description'   => 'Описание',
        'min_amount'    => 'Минимальная сумма корзины',
        'discount'      => 'Скидка',
        'discount_type' => 'Тип скидки',
        'begin'         => 'Начало действия',
        'end'           => 'Завершение действия',
        'limit'         => 'Лимит',
        'usages'        => 'Использовано',
        'active'        => 'Включен',
        'categories'    => 'Категории',
        'products'      => 'Товары ',
    ];
    public array $config = [
        'controller'     => 'onetable',
        'table'          => 'promocodes',
        'idType'         => 'documents',
        'display'        => 100,
        'ignoreEmpty'    => true,
        'returnDLObject' => true,
        'addWhereList'   => '',
        'orderBy'        => 'id ASC',
    ];

    public function __construct(\DocumentParser $modx)
    {
        $this->modx = $modx;
    }

    public function start()
    {
        $_SESSION['RecordsProcessed'] = 0;
        $_SESSION['RecordsTotal'] = 0;
        $_SESSION['LastRecordId'] = 0;
        $_SESSION['complete'] = false;
        $config = $this->config;
        $this->addFilters($config);
        unset($config['display']);
        $dl = $this->modx->runSnippet('DocLister', $config);

        $linesTotal = $_SESSION['RecordsTotal'] = $dl->getChildrenCount();

        return ['status' => true];
    }

    public function process()
    {
        $processed = $_SESSION['RecordsProcessed'];
        $lastId = $_SESSION['LastRecordId'];
        $config = $this->config;
        $this->addFilters($config);
        if(!empty($config['addWhereList'])) {
            $config['addWhereList'] .= ' AND ';
        }
        $config['addWhereList'] .= 'id > ' . (int)$lastId;
        $docs = $this->modx->runSnippet('DocLister', $config)->getDocs();
        $data = [];
        if(!$processed) {
            $data[] = array_values($this->fields);
        }
        foreach ($docs as $id => $doc) {
            $doc['active'] = $doc['active'] == 1 ? 'Да' : 'Нет';
            $doc['discount_type'] = $doc['discount_type'] == 1 ? 'Сумма' : 'Процент';
            $doc['categories'] = $doc['products'] = '';
            $row = [];
            foreach ($this->fields as $field => $title) {
                if (isset($doc[$field])) {
                    $row[] = $doc[$field];
                } else {
                    $row[] = '';
                }
            }
            $data[] = $row;
            $processed++;
            $_SESSION['LastRecordId'] = $id;
        }
        $_SESSION['RecordsProcessed'] = $processed;
        if ($_SESSION['RecordsProcessed'] >= $_SESSION['RecordsTotal']) {
            $_SESSION['complete'] = true;
        }
        $out = [
            'processed' => $_SESSION['RecordsProcessed'],
            'total'     => $_SESSION['RecordsTotal'],
            'complete'  => $_SESSION['complete'],
            'last'      => $_SESSION['LastRecordId']
        ];
        $out['data'] = $data;
        $out['status'] = true;

        return $out;
    }
}
