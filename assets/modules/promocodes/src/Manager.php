<?php

namespace Pathologic\Commerce\Promocodes;

use Commerce\Carts\ProductsCart;
use Commerce\SettingsTrait;

class Manager
{
    use SettingsTrait;

    protected $modx;
    protected $model;
    protected $messages = [];

    public function __construct(\DocumentParser $modx, $params = [])
    {
        $this->modx = $modx;
        $this->model = new Model($modx);
        $this->setSettings($params);
    }

    /**
     * @param $promocode
     * @param $instance
     * @return bool|array
     */
    public function register($promocode, $instance = 'products')
    {
        $out = false;
        if ($this->model->load($promocode, true)->getID()) {
            $prevent = false;
            $result = $this->modx->invokeEvent('OnBeforePromocodeRegister', [
                'instance'  => $instance,
                'promocode' => $this->model,
                'prevent'   => &$prevent
            ]);
            if(!empty($result)) {
                $this->addMessages($result);
            }
            if ($prevent) {
                return false;
            }
            $_SESSION['promocodes'][$instance] = $this->model->get('promocode');
            $result = $this->modx->invokeEvent('OnPromocodeRegister', [
                'instance'  => $instance,
                'promocode' => $this->model
            ]);
            if(!empty($result)) {
                $this->addMessages($result);
            }
            $out = true;
        }

        return $out;
    }

    /**
     * @param $instance
     * @return false|mixed
     */
    public function get($instance = 'products') {
        return is_scalar($instance) && isset($_SESSION['promocodes'][$instance]) ? $_SESSION['promocodes'][$instance] : false;
    }

    /**
     * @param $instance
     * @return array|false
     */
    public function apply($instance = 'products')
    {
        if (isset($_SESSION['promocodes'][$instance]) && $this->model->load($_SESSION['promocodes'][$instance])->getID()) {
            /** @var ProductsCart $cart */
            if ($cart = ci()->carts->getCart($instance)) {
                $total = 0;
                $prevent = false;
                $this->modx->invokeEvent('OnBeforePromocodeApply', [
                    'instance'  => $instance,
                    'cart'      => $cart,
                    'total'     => &$total,
                    'promocode' => $this->model,
                    'prevent'   => &$prevent
                ]);
                if(!empty($result)) {
                    $this->addMessages($result);
                }
                if ($prevent) {
                    return false;
                }
                $categoriesLinks = $this->model->getCategoriesLinks();
                $productsLinks = $this->model->getProductsLinks();
                if ($total > 0) {
                } elseif (!empty($categoriesLinks) || !empty($productsLinks)) {
                    $items = $cart->getItems();
                    foreach ($items as $row => $item) {
                        $parents = array_values($this->modx->getParentIds($item['id']));
                        if (array_intersect($parents, $categoriesLinks) || in_array($item['id'], $productsLinks)) {
                            $total += $item['price'] * $item['count'];
                        }
                    }
                } else {
                    $total = $cart->getTotal();
                }
                $min_amount = ci()->currency->convertToActive($this->model->get('min_amount'));
                $discount = $this->model->get('discount');
                $type = $this->model->get('discount_type');
                if ($total && $total >= $min_amount) {
                    $out = [
                        'price' => -1 * round(!$type ? ($total * $discount / 100) : ci()->currency->convertToActive($discount)),
                        'title' => \DLTemplate::getInstance($this->modx)->parseChunk(ci()->promocodes->getSetting('discountTitle'), $this->model->toArray())
                    ];
                    $this->modx->invokeEvent('OnPromocodeApply', [
                        'instance'       => $instance,
                        'cart'           => $cart,
                        'total'          => $total,
                        'promocode'      => $this->model,
                        'discount_price' => &$out['price'],
                        'discount_title' => &$out['title']
                    ]);

                    return $out;
                }
            }
        }

        return false;
    }

    public function remove($instance = 'products')
    {
        unset($_SESSION['promocodes'][$instance]);
    }

    public function removeAll()
    {
        unset($_SESSION['promocodes']);
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param  array  $messages
     */
    public function addMessages(array $messages): void
    {
        foreach ($messages as $message) {
            if (is_scalar($message)) {
                $this->messages[] = $message;
            }
        }
    }
}
