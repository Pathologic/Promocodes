<?php

namespace Pathologic\Commerce\Promocodes;

class Plugin
{
    protected $modx;
    protected $params = [];

    public function __construct(\DocumentParser $modx, array $params = [])
    {
        $this->modx = $modx;
        $this->params = $params;
    }

    public function OnPageNotFound()
    {
        if (function_exists('ci') && !empty($_GET['q']) && is_scalar($_GET['q']) && strpos($_GET['q'],
                'promocodes/action') === 0) {
            $action = isset($_POST['action']) && is_scalar($_POST['action']) ? $_POST['action'] : 'remove';
            $instance = isset($_POST['instance']) && is_scalar($_POST['instance']) ? $_POST['instance'] : 'products';
            $out = ['status' => false];
            if ($action == 'remove') {
                ci()->promocodes->remove($instance);
                $out['status'] = true;
            } elseif ($action == 'register') {
                $promocode = isset($_POST['promocode']) && is_scalar($_POST['promocode']) ? trim($_POST['promocode']) : '';
                if (!empty($promocode) && ci()->promocodes->register($promocode, $instance)) {
                    $out['status'] = true;
                } else {
                    ci()->promocodes->remove($instance);
                }
                $out['messages'] = ci()->promocode->getMessages();
            }

            echo json_encode($out);
            die();
        }
    }

    public function OnCommerceInitialized()
    {
        $modx = $this->modx;
        $params = $this->params;
        if (!ci()->has('promocodes')) {
            ci()->set('promocodes', function ($ci) use ($modx, $params) {
                return new Manager($modx, $params);
            });
        }
    }

    public function OnBeforeOrderProcessing()
    {
        $FL = $this->params['FL'];
        $cartInstance = $FL->getCFGDef('cartName', 'products');
        $promocode = ci()->promocodes->get($cartInstance);
        if (!$promocode || !ci()->promocodes->register($promocode, 'order')) {
            $messages = ci()->promocodes->getMessages();
            if (empty($messages)) {
                $FL->addMessage('Промокод недействителен');
            } else {
                foreach ($messages as $message) {
                    $FL->addMessage($message);
                }
            }
            ci()->promocodes->remove($cartInstance);
            $this->params['prevent'] = true;
        }
    }

    public function OnCollectSubtotals()
    {
        $instance = $this->params['instance'] ?? 'order';
        $discount = ci()->promocodes->apply($instance);
        if ($discount) {
            $this->params['rows']['promocode'] = $discount;
            $this->params['total'] += $discount['price'];
        } else {
            ci()->promocodes->remove('order');
        }
    }

    public function OnOrderSaved()
    {
        if ($this->params['mode'] == 'new' && ($promocode = ci()->promocodes->get('order'))) {
            $model = new Model($this->modx);
            if ($model->load($promocode)->getID()) {
                $model->saveOrder($this->params['order_id']);
            }
        }
    }
}
