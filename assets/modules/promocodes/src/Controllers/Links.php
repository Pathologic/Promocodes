<?php

namespace Pathologic\Commerce\Promocodes\Controllers;

use Pathologic\Commerce\Promocodes\Model;

class Links
{
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
            'controller'   => \Pathologic\Commerce\Promocodes\DocLister\Links::class,
            'idType'       => 'documents',
            'ignoreEmpty'  => true,
            'makeUrl'      => false,
            'display'      => 15,
            'selectFields' => 'c.id,c.link,sc.pagetitle',
            'returnDLObject' => true
        ];
        $type = isset($_POST['type']) && $_POST['type'] == 1 ? 1 : 0;
        $pcid = isset($_POST['pcid']) && is_numeric($_POST['pcid']) ? (int)$_POST['pcid'] : 0;

        $config['addWhereList'] = "`c`.`type` = {$type} AND `c`.`pcid` = {$pcid}";
        $this->modx->invokeEvent('OnBeforePromocodeLinksLoad', [
            'config' => &$config
        ]);
        $docs = $this->modx->runSnippet('DocLister', $config)->getDocs();
        $this->modx->invokeEvent('OnPromocodeLinksLoad', [
            'docs' => &$docs
        ]);

        return array_values($docs);
    }

    public function add()
    {
        $out = ['status' => false];
        $pcid = isset($_POST['pcid']) && is_numeric($_POST['pcid']) ? (int)$_POST['pcid'] : 0;
        $link = isset($_POST['link']) && is_numeric($_POST['link']) ? (int)$_POST['link'] : [];
        $type = isset($_POST['type']) && $_POST['type'] == 1 ? 1 : 0;

        if($pcid && $link && $this->model->edit($pcid)->getID()) {
            if ($type == 0) {
                $this->model->addCategoriesLinks([$link]);
            } else {
                $this->model->addProductsLinks([$link]);
            }
            $out['status'] = true;
        }

        return $out;
    }

    public function search()
    {
        $config = [
            'controller'   => 'site_content',
            'idType'       => 'documents',
            'ignoreEmpty'  => true,
            'makeUrl'      => false,
            'display'      => 15,
            'selectFields' => 'c.id,c.id as link,c.pagetitle',
            'orderBy'      => 'id desc',
            'returnDLObject' => true
        ];
        $search = isset($_POST['q']) && is_scalar($_POST['q']) ? $this->modx->db->escape($_POST['q']) : '';
        $type = isset($_POST['type']) && $_POST['type'] == 1 ? 1 : 0;
        $where = [];
        if($type) {
            $templateIds = ci()->promocodes->getSetting('productTemplates');
        } else {
            $templateIds = ci()->promocodes->getSetting('categoryTemplates');
        }
        $templateIds = \APIhelpers::cleanIDs($templateIds);
        if($templateIds) {
            $templateIds = implode(',', $templateIds);
            $where[] = "`c`.`template` IN ({$templateIds})";
        }
        if(!empty($search)) {
            $where[] = "`c`.`pagetitle` LIKE '%{$search}%'";
        }
        $config['addWhereList'] = implode(' AND ', $where);
        $this->modx->invokeEvent('OnBeforePromocodeLinksSearch', [
            'config' => &$config
        ]);

        $docs = $this->modx->runSnippet('DocLister', $config)->getDocs();
        $this->modx->invokeEvent('OnPromocodeLinksSearch', [
            'docs' => &$docs
        ]);

        return array_values($docs);
    }

    public function delete()
    {
        $out = ['status' => false];

        $pcid = isset($_POST['pcid']) && is_numeric($_POST['pcid']) ? (int)$_POST['pcid'] : 0;
        $link = isset($_POST['link']) && is_numeric($_POST['link']) ? (int)$_POST['link'] : [];
        if($pcid && $link) {
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('promocodes_links')} WHERE `pcid` = {$pcid} AND `link` = {$link}");
            $out['status'] = true;
        }

        return $out;
    }
}
