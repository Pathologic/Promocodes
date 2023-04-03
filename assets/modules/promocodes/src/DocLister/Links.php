<?php

namespace Pathologic\Commerce\Promocodes\DocLister;

class Links extends \onetableDocLister
{
    protected $table = 'promocodes_links';

    public function __construct($modx, $cfg = [], $startTime = null)
    {
        parent::__construct($modx, $cfg, $startTime);
        $this->setFiltersJoin(" LEFT JOIN {$this->getTable('site_content', 'sc')} ON `c`.`link` = `sc`.`id`");
    }
}
