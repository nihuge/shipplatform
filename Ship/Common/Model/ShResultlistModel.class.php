<?php

namespace Common\Model;

use Common\Model\BaseModel;

/**
 * 舱计算数据
 */
class ShResultlistModel extends BaseModel
{
    /**
     * 获取列表
     * */
    public function getlist($resultid)
    {
        $list = $this
            ->where(array('resultid' => $resultid))
            ->select();
        $result = '';
        foreach ($list as $k => $v) {
            $result[$v['solt']][] = $v;
        }
        return $result;
    }
}