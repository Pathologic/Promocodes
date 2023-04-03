<?php

namespace Pathologic\Commerce\Promocodes\Controllers;

use Pathologic\Commerce\Promocodes\DocLister\FiltersTrait;
use Pathologic\Commerce\Promocodes\Model;
use Pathologic\Commerce\Promocodes\UniquePromocodeException;

class Controller
{
    use FiltersTrait;

    protected $modx;
    protected $model;

    public function __construct(\DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->model = new Model($modx);
    }

    public function list()
    {
        $config = [
            'controller'      => 'onetable',
            'table'           => 'promocodes',
            'idType'          => 'documents',
            'ignoreEmpty'     => 1,
            'display'         => 25,
            'offset'          => 0,
            'sortBy'          => 'id',
            'selectFields'    => 'c.*',
            'sortDir'         => 'desc',
            'returnDLObject' => true
        ];

        $this->addDynamicConfig($config);
        $this->addFilters($config);
        $dl = $this->modx->runSnippet('DocLister', $config);
        $total = $dl->getChildrenCount();
        $docs = $dl->getDocs();
        if($docs) {
            $ids = implode(',', array_keys($docs));
            $q = $this->modx->db->query("SELECT DISTINCT `pcid` FROM {$this->modx->getFullTableName('promocodes_links')} WHERE `type` = 0 AND `pcid` IN (${ids})");
            $categories = $this->modx->db->getColumn('pcid', $q) ?: [];
            $q = $this->modx->db->query("SELECT DISTINCT `pcid` FROM {$this->modx->getFullTableName('promocodes_links')} WHERE `type` = 0 AND `pcid` IN (${ids})");
            $products = $this->modx->db->getColumn('pcid', $q) ?: [];
            foreach ($docs as $id => &$doc) {
                $doc['categories'] = in_array($id, $categories);
                $doc['products'] = in_array($id, $products);
            }
        }

        return ['rows' => array_values($docs), 'total' => $total];
    }

    protected function addDynamicConfig(&$config)
    {
        if (isset($_POST['rows'])) {
            $config['display'] = (int) $_POST['rows'];
        }
        $offset = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $offset = $offset ? $offset : 1;
        $offset = $config['display'] * abs($offset - 1);
        $config['offset'] = $offset;
        if (isset($_POST['sort'])) {
            $config['sortBy'] = '`' . preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['sort']) . '`';
        }
        if (isset($_POST['order']) && in_array(strtoupper($_POST['order']), ["ASC", "DESC"])) {
            $config['sortDir'] = $_POST['order'];
        }
    }

    protected function getFormParams()
    {
        return [
            'formid'         => 'promocode',
            'api'            => 1,
            'noemail'        => 1,
            'protectSubmit'  => 0,
            'submitLimit'    => 0,
            'filters'        => [
                'promocode'   => ['strip_tags', 'trim'],
                'pattern'     => ['strip_tags', 'trim'],
                'description' => ['strip_tags', 'trim', 'removeExtraSpaces'],
                'min_amount'  => ['castFloat'],
                'discount'    => ['castFloat'],
                'limit'       => ['castInt'],
                'amount'      => ['castInt'],
            ],
            'rules'          => [
                'promocode' => [
                    'required' => 'Введите или сгенерируйте промокод'
                ],
                '!begin'    => [
                    'date' => [
                        'params'  => 'Y-m-d H:i:s',
                        'message' => 'Введите дату правильно'
                    ]
                ],
                '!end'      => [
                    'date' => [
                        'params'  => 'Y-m-d H:i:s',
                        'message' => 'Введите дату правильно'
                    ]
                ],
                'discount'  => [
                    'greater' => [
                        'params'  => 0,
                        'message' => 'Значение должно быть больше 0'
                    ]
                ]
            ],
            'prepareProcess' => function ($modx, $data, $FormLister, $name) {
                $data['limit'] = !isset($data['limit']) || $data['limit'] < 0 ? 0 : $data['limit'];
                $data['discount'] = !isset($data['discount']) || $data['discount'] < 0 ? 0 : $data['discount'];
                $data['discount_type'] = !isset($data['discount_type']) || $data['discount_type'] == 0 ? 0 : 1;
                $data['min_amount'] = !isset($data['min_amount']) || $data['min_amount'] < 0 ? 0 : $data['min_amount'];
                $id = (int) $FormLister->getField('id');
                $model = new Model($modx);
                if ($id && $id == $model->edit($id)->getID()) {
                    $model->fromArray($data);
                } else {
                    $model->create($data);
                }
                $timeBegin = $timeEnd = 0;
                if (!empty($data['begin'])) {
                    $timeBegin = strtotime($data['begin']);
                }
                if (!empty($data['end'])) {
                    $timeEnd = strtotime($data['end']);
                }
                if ($timeBegin && $timeEnd && $timeBegin > $timeEnd) {
                    $model->set('begin', $data['end']);
                    $model->set('end', $data['begin']);
                }
                $discount_type = (int) $FormLister->getField('discount_type', 0);
                $discount = (float) $FormLister->getField('discount', 0);
                $min_amount = (float) $FormLister->getField('min_amount', 0);
                if ($discount_type && $discount > $min_amount) {
                    $FormLister->addError('min_amount', 'required',
                        'Минимальная сумма заказа не должна быть меньше скидки');

                    return;
                }
                try {
                    $result = $model->save(true, false);
                } catch (UniquePromocodeException $e) {
                    $FormLister->addError('promocode', 'unique', 'Такой промокод уже существует');

                    return;
                }
                if (!$result) {
                    $FormLister->setValid(false);
                    if ($messages = $model->getMessages()) {
                        foreach ($messages as $message) {
                            $FormLister->addMessage($message);
                        }
                    } else {
                        $FormLister->addMessage('Не удалось сохранить промокод');
                    }
                } else {
                    if (!empty($data['categories']) && is_array($data['categories'])) {
                        $model->addCategoriesLinks($data['category']);
                    }
                    if (!empty($data['products']) && is_array($data['products'])) {
                        $model->addCategoriesLinks($data['product']);
                    }
                }
            }
        ];
    }

    public function create()
    {
        return $this->modx->runSnippet('FormLister', $this->getFormParams());
    }

    public function update()
    {
        $params = $this->getFormParams();
        $params['rules']['id'] = [
            'required' => '',
            'greater'  => [
                'params'  => 0,
                'message' => ''
            ]
        ];

        return $this->modx->runSnippet('FormLister', $params);
    }

    public function generate()
    {
        $params = $this->getFormParams();
        unset($params['rules']['promocode']);
        $params['rules']['pattern'] = [
            'required' => 'Введите паттерн для генерации',
        ];
        $params['rules']['amount'] = [
            'required' => 'Введите количество',
            'greater'  => [
                'params'  => 0,
                'message' => 'Количество должно быть от 1'
            ]
        ];
        $params['prepareProcess'] = function ($modx, $data, $FormLister, $name) {
            $data['limit'] = !isset($data['limit']) || $data['limit'] < 0 ? 0 : $data['limit'];
            $data['discount'] = !isset($data['discount']) || $data['discount'] < 0 ? 0 : $data['discount'];
            $data['discount_type'] = !isset($data['discount_type']) || $data['discount_type'] == 0 ? 0 : 1;
            $data['min_amount'] = !isset($data['min_amount']) || $data['min_amount'] < 0 ? 0 : $data['min_amount'];
            $amount = (int) $FormLister->getField('amount');
            $pattern = $FormLister->getField('pattern');
            $timeBegin = $timeEnd = 0;
            if (!empty($data['begin'])) {
                $timeBegin = strtotime($data['begin']);
            }
            if (!empty($data['end'])) {
                $timeEnd = strtotime($data['end']);
            }
            if ($timeBegin && $timeEnd && $timeBegin > $timeEnd) {
                $begin = $data['end'];
                $end = $data['begin'];
                $data['begin'] = $begin;
                $data['end'] = $end;
            }
            $discount_type = (int) $FormLister->getField('discount_type', 0);
            $discount = (float) $FormLister->getField('discount', 0);
            $min_amount = (float) $FormLister->getField('min_amount', 0);
            if ($discount_type && $discount > $min_amount) {
                $FormLister->addError('min_amount', 'required',
                    'Минимальная сумма заказа не должна быть меньше скидки');

                return;
            }
            $model = new Model($modx);
            $result = $model->generate($pattern, $data, $amount, true);

            if (!$result) {
                $FormLister->setValid(false);
                $FormLister->addMessage('Не удалось сгенерировать промокоды');
            } else {
                $FormLister->addMessage('Сгенерировано промокодов: ' . count($result));
            }
        };

        return $this->modx->runSnippet('FormLister', $params);
    }

    public function get()
    {
        $out = ['status' => false];
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($this->model->edit($id)->getID()) {
            $out['status'] = true;
            $out['fields'] = $this->model->toArray();
        }

        return $out;
    }

    public function toggleActive()
    {
        $out = ['status' => false];
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $active = isset($_POST['active']) ? (int) $_POST['active'] : 0;
        if ($this->model->edit($id)->getID() && $this->model->set('active', $active)->save()) {
            $out['status'] = true;
        }

        return $out;
    }

    public function delete()
    {
        $out = ['status' => false];

        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        $out['status'] = $this->model->delete($ids);
        if(!$out['status']) {
            $out['messages'] = $this->model->getMessages();
        }

        return $out;
    }
}
