<?php

use Atshike\YidaApi\Services\YiDaServices;

$yd = new YiDaServices();
$data = getData($yd,'FORM-******', 1, 'user_id', [ 'feilds' => 'RUNNING']);

function getData($yd, $formUuid, $next, $user_id, $params, $data = [])
{
    $rs = $yd->getFormList($formUuid, $user_id, $params, $next, 10);
    $list = $rs->body?->data;
    $currentPage = $rs->body?->currentPage; // 当前页
    $totalCount = $rs->body?->totalCount; // 实例总数
    $data = array_merge($data, (array) $list);
    if ($currentPage < ceil($totalCount / 100)) {
        echo "下一页：{$currentPage}/$totalCount \n";
        $data = getData($yd, $formUuid, bcadd($next, 1), $user_id, $params, $data);
    }

    return $data;
}
