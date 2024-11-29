<?php

namespace ssqz\widgets;

use yii\helpers\Url;

class GridView extends \yii\grid\GridView
{
    public $tableOptions = [
        'class' => 'table table-condensed table-hover'
    ];

    public $summaryOptions = [
        'class' => 'h5 pull-left',
    ];

    public $pager = [
        'prevPageLabel' => '&lsaquo;',
        'nextPageLabel' => '&rsaquo;',
        'firstPageLabel' => '&laquo;',
        'lastPageLabel' => '&raquo;',
        'options' => ['class' => 'pagination pull-right reset margin top bottom'],
    ];

    public function init()
    {
        parent::init();
        $param = \Yii::$app->request->queryParams;
        $param[0] = '';
        $btns = '';
        foreach ([20, 50, 100, 200, 500, 1000] as $pp) {
            $param['per-page'] = $pp;
            $param['page'] = null;
            $btns .= '<a href="' . Url::to($param) . '" class="btn btn-default">' . $pp . '</a> ';
        }
        $perPage = "<div class=\"pagination pull-right reset margin\"><div class=\"btn-group\">{$btns}</div></div>";
        $this->layout = "{items}\n{summary}\n{pager}\n{$perPage}\n";
    }

}
