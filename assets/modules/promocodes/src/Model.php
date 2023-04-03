<?php

namespace Pathologic\Commerce\Promocodes;

use ReverseRegex\Exception;
use ReverseRegex\Generator\Scope;
use ReverseRegex\Lexer;
use ReverseRegex\Parser;
use ReverseRegex\Random\MersenneRandom;

if (!class_exists('ReverseRegex\Lexer')) {
    include_once MODX_BASE_PATH . 'assets/modules/promocodes/vendor/autoload.php';
}

class Model extends \autoTable
{
    use Messages;

    protected $table = 'promocodes';
    protected $links_table = 'promocodes_links';
    protected $orders_table = 'promocodes_orders';
    public $default_field = [
        'promocode'     => '',
        'description'   => '',
        'begin'         => null,
        'end'           => null,
        'min_amount'    => 0,
        'discount'      => 0,
        'discount_type' => 0,
        'limit'         => 1,
        'active'        => 1,
        'createdon'     => null,
        'updatedon'     => null,
    ];
    protected $categoriesLinks = [];
    protected $productsLinks = [];
    public $pattern = '[A-Z0-9]{10}';

    /**
     * @param  string  $pattern
     * @param  array  $data
     * @param  int  $maxAmount
     * @param $fire_events
     * @return array
     * @throws \Exception
     */
    public function generate(string $pattern, array $data, int $maxAmount, $fire_events = true)
    {
        $lexer = new Lexer($pattern);
        $randomizer = new MersenneRandom();
        $parser = new Parser($lexer, new Scope(), new Scope());
        $generated = [];
        $categories = $data['categories'] ?? [];
        $products = $data['products'] ?? [];
        try {
            $generator = $parser->parse()->getResult();
            for ($i = 1; $i <= $maxAmount; $i++) {
                $result = '';
                $generator->generate($result, $randomizer);
                try {
                    if ($this->create($data)->set('promocode', $result)->save($fire_events, false)) {
                        if (is_array($categories)) {
                            $this->addCategoriesLinks($categories);
                        }
                        if (is_array($products)) {
                            $this->addCategoriesLinks($products);
                        }
                        $generated[] = $result;
                    }
                } catch (UniquePromocodeException $e) {

                }
            }
        } catch (Exception $e) {

        }

        return $generated;
    }

    /**
     * @param $pattern
     * @return string
     */
    public function getPromocode($pattern = '')
    {
        if (empty($pattern)) {
            $pattern = $this->pattern;
        }
        $lexer = new Lexer($pattern);
        $randomizer = new MersenneRandom();
        $parser = new Parser($lexer, new Scope(), new Scope());
        try {
            $generator = $parser->parse()->getResult();
            $result = '';
            $generator->generate($result, $randomizer);

            return $result;
        } catch (Exception $e) {
            return '';
        }
    }

    protected function getLinks()
    {
        $id = $this->getID();
        $categories = $products = [];
        if ($id) {
            $q = $this->query("SELECT * FROM {$this->makeTable('promocodes_links')} where `pcid` = {$id}");
            while ($row = $this->modx->db->getRow($q)) {
                if ($row['type']) {
                    $products[] = $row['link'];
                } else {
                    $categories[] = $row['link'];
                }
            }
        }
        $this->categoriesLinks = $categories;
        $this->productsLinks = $products;

        return $this;
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->getLinks();

        return $this;
    }

    public function load($promocode, $active = false)
    {
        $promocode = is_scalar($promocode) ? trim($promocode) : '';
        $promocode = $this->escape($promocode);
        $sql = "SELECT `id` FROM {$this->makeTable($this->table)} WHERE BINARY `promocode`='{$promocode}'";
        if ($active) {
            $now = date('Y-m-d H:i:s', $this->getTime(time()));
            $sql .= " AND `active`=1 AND (`begin` IS NULL OR `begin` <= '{$now}') AND (`end` IS NULL OR `end` >= '{$now}') AND (`limit` = 0 OR `limit` > `usages`)";
        }
        $q = $this->query($sql);
        if ($id = $this->modx->db->getValue($q)) {
            $this->edit($id);
        }

        return $this;
    }

    public function saveOrder($order)
    {
        if ($id = $this->getID()) {
            $order = (int) $order;
            $data = $this->escape(json_encode($this->toArray(), JSON_UNESCAPED_UNICODE));
            $this->query("INSERT IGNORE INTO {$this->makeTable($this->orders_table)} (`pcid`, `order`, `data`) VALUES ({$id}, {$order}, '{$data}')");
            $this->query("UPDATE {$this->makeTable($this->table)} SET `usages` = `usages` + 1 WHERE `id`={$id}");
        }
    }


    /**
     * @return array|mixed
     */
    public function getCategoriesLinks()
    {
        return $this->categoriesLinks;
    }

    /**
     * @return array|mixed
     */
    public function getProductsLinks()
    {
        return $this->productsLinks;
    }

    /**
     * @param  array  $links
     * @return $this
     * @throws \Exception
     */
    public function addCategoriesLinks(array $links = [])
    {
        $links = \APIhelpers::cleanIDs($links);
        $id = $this->getID();
        if ($id && $links) {
            $values = [];
            foreach ($links as $link) {
                $values[] = "({$id}, {$link}, 0)";
            }
            $values = implode(',', $values);
            $this->query("INSERT IGNORE INTO {$this->makeTable('promocodes_links')} (`pcid`, `link`, `type`) VALUES {$values}");
            $this->categoriesLinks = $links;
        }

        return $this;
    }

    /**
     * @param  array  $links
     * @param $fire_events
     * @return $this
     * @throws \Exception
     */
    public function addProductsLinks(array $links = [])
    {
        $links = \APIhelpers::cleanIDs($links);
        $id = $this->getID();
        if ($id && $links) {
            $values = [];
            foreach ($links as $link) {
                $values[] = "({$id}, {$link}, 1)";
            }
            $values = implode(',', $values);
            $this->query("INSERT IGNORE INTO {$this->makeTable('promocodes_links')} (`pcid`, `link`, `type`) VALUES {$values}");
            $this->productsLinks = $links;
        }

        return $this;
    }

    /**
     * @param $fire_events
     * @param $clearCache
     * @return bool|void|null
     */
    public function save($fire_events = false, $clearCache = false)
    {
        $out = false;
        $mode = $this->newDoc ? 'new' : 'upd';
        if (!$this->get('promocode')) {
            $this->set('promocode', $this->getPromocode());
        }
        if (empty($this->get('begin'))) {
            $this->eraseField('begin');
        }
        if (empty($this->get('end'))) {
            $this->eraseField('end');
        }
        $result = $this->getInvokeEventResult('OnBeforePromocodeSave', [
            'mode'      => $mode,
            'promocode' => $this,
        ], $fire_events);
        if (!empty($result)) {
            $this->addMessages($result);
        } else {
            if (!$this->checkUnique($this->table, 'promocode')) {
                throw new UniquePromocodeException('Такой промокод уже существует!');
            }
            if ($this->newDoc) {
                if (!$this->get('createdon')) {
                    $this->set('createdon', date('Y-m-d H:i:s', $this->getTime(time())));
                }
            } else {
                if (!$this->get('updatedon')) {
                    $this->set('updatedon', date('Y-m-d H:i:s', $this->getTime(time())));
                }
            }
            if ($out = parent::save($fire_events, false)) {
                $this->invokeEvent('OnPromocodeSave', [
                    'mode'      => $mode,
                    'promocode' => $this,
                ], $fire_events);
            }
        }

        return $out;
    }

    public function delete($ids, $fire_events = false, $clearCache = false)
    {
        $out = false;
        $ids = \APIhelpers::cleanIDs($ids);
        if ($ids) {
            $result = $this->getInvokeEventResult('OnBeforePromocodeDelete', [
                'ids' => &$ids
            ], $fire_events);
            if (!empty($result)) {
                $this->addMessages($result);
            } else {
                $out = parent::delete($ids, $fire_events);
                $this->invokeEvent('OnPromocodeDelete', [
                    'ids' => $ids
                ], $fire_events);
            }
        }

        return $out;
    }

    public function createTable()
    {
        $this->query("CREATE TABLE IF NOT EXISTS {$this->makeTable($this->table)} (
            `id` INT(11) AUTO_INCREMENT,
            `promocode` VARCHAR(255) DEFAULT NULL,
            `description` TEXT,
            `begin` DATETIME,
            `end` DATETIME,
            `min_amount` DECIMAL(16,2) NOT NULL DEFAULT 0,
            `discount` DECIMAL(16,2) NOT NULL,
            `discount_type` TINYINT(1) NOT NULL DEFAULT 0,
            `limit` INT(11) NOT NULL DEFAULT 1,
            `usages` INT(11) NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `createdon` DATETIME DEFAULT CURRENT_TIMESTAMP ,
            `updatedon` DATETIME,
            PRIMARY KEY (`id`),
            UNIQUE KEY `promocode` (`promocode`),
            KEY `use_period` (`begin`, `end`),
            KEY `active` (`active`)
            ) ENGINE=InnoDB
        ");
        $this->query("CREATE TABLE IF NOT EXISTS {$this->makeTable($this->links_table)} (
            `id` INT(11) AUTO_INCREMENT,
            `pcid` INT(11) NOT NULL,
            `link` INT(11) NOT NULL,
            `type` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `link` (`pcid`, `link`, `type`),
            CONSTRAINT `promocodes_links_ibfk_1`
            FOREIGN KEY (`pcid`)
            REFERENCES {$this->makeTable($this->table)} (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB
        ");
        $this->query("CREATE TABLE IF NOT EXISTS {$this->makeTable($this->orders_table)} (
            `pcid` INT(11) NOT NULL,
            `order` INT(11) NOT NULL,
            `data` TEXT NOT NULL,
            UNIQUE KEY `order` (`order`),
            CONSTRAINT `promocodes_orders_ibfk_1`
            FOREIGN KEY (`order`)
            REFERENCES {$this->makeTable('commerce_orders')} (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB
        ");
        $this->query("
            INSERT IGNORE INTO {$this->makeTable('system_eventnames')} (`name`, `groupname`) VALUES
            ('OnBeforePromocodeSave', 'Promocodes Events'),
            ('OnPromocodeSave', 'Promocodes Events'),
            ('OnBeforePromocodeApply', 'Promocodes Events'),
            ('OnPromocodeApply', 'Promocodes Events'),
            ('OnBeforePromocodeRegister', 'Promocodes Events'),
            ('OnPromocodeRegister', 'Promocodes Events'),
            ('OnBeforePromocodeDelete', 'Promocodes Events'),
            ('OnPromocodeDelete', 'Promocodes Events'),
            ('OnBeforePromocodeLinksSearch', 'Promocodes Events'),
            ('OnPromocodeLinksSearch', 'Promocodes Events'),
            ('OnBeforePromocodeLinksLoad', 'Promocodes Events'),
            ('OnPromocodeLinksLoad', 'Promocodes Events')
        ");
    }
}
