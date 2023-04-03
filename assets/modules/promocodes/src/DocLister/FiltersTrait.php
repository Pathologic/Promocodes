<?php

namespace Pathologic\Commerce\Promocodes\DocLister;

trait FiltersTrait
{
    protected function addFilters(&$config)
    {
        $where = [];
        $promocode = !empty($_POST['promocode']) && is_scalar($_POST['promocode']) ? $this->modx->db->escape(trim($_POST['promocode'])) : '';
        $begin = !empty($_POST['begin']) && is_scalar($_POST['begin']) ? $this->modx->db->escape($_POST['begin']) : '';
        $end = !empty($_POST['end']) && is_scalar($_POST['end']) ? $this->modx->db->escape($_POST['end']) : '';
        $active = isset($_POST['active']) && $_POST['active'] == 1 ? 1 : 0;
        if($promocode) {
            $where[] = "`promocode` LIKE '{$promocode}%'";
        }
        if($begin && strtotime($begin)) {
            $where[] =  "(`begin` IS NULL OR `begin` >= '{$begin}')";
        }
        if($end && strtotime($end)) {
            $where[] =  "(`end` IS NULL OR `end` <= '{$end}')";
        }
        if($active) {
            $where[] = 'c.active = 1 AND (c.limit = 0 OR c.limit > c.usages)';
            if((empty($begin) && empty($end)) || (!strtotime($begin) && !strtotime($end))) {
                $now = date('Y-m-d H:i:s', time() + $this->modx->getConfig('server_offset_time'));
                $where[] = "(`begin` IS NULL OR `begin` <= '{$now}') AND (`end` IS NULL OR `end` >= '{$now}')";
            }
        }
        if($where) {
            $config['addWhereList'] = implode(' AND ', $where);
        }
    }
}
