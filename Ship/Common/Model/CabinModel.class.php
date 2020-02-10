<?php

namespace Common\Model;

use Common\Model\BaseModel;

/**
 * 舱Model
 * */
class CabinModel extends BaseModel
{
    /**
     * 自动验证
     */
    protected $_validate = array(
        array('cabinname', '1,10', '船舱名称长度不能超过10个字符', 0, 'length'),//存在即验证 长度不能超过4个字符
        array('cabinname', 'require', '船舱名称不能为空', self::EXISTS_VALIDATE),//存在即验证 不能为空
        array('altitudeheight', '0,6', '基准高度长度不能超过6个字符', 0, 'length'),//存在即验证 长度不能超过6个字符
        array('dialtitudeheight', '0,6', '底量基准高度长度不能超过6个字符', 0, 'length'),//存在即验证 长度不能超过6个字符
        array('bottom_volume', '0,6', '底量长度不能超过6个字符', 0, 'length'),//存在即验证 长度不能超过6个字符
        array('bottom_volume_di', '0,6', '底量底量长度不能超过6个字符', 0, 'length'),//存在即验证 长度不能超过6个字符
        array('pipe_line', '0,6', '管线长度不能超过6个字符', 0, 'length'),//存在即验证 长度不能超过6个字符
    );

    /**
     * 获取船的舱列表
     * @param array data 数据
     * @return array
     * @return @param code 返回码
     * @return @param content 说明、内容
     */
    public function cabinlist($data)
    {
        $user = new \Common\Model\UserModel();
        //判断用户状态、公司状态、标识比对
        $msg1 = $user->is_judges($data['uid'], $data['imei']);
        if ($msg1['code'] == '1') {
            $where = array(
                'shipid' => $data['shipid']
            );
            $list = $this
                ->field('id,cabinname,altitudeheight,dialtitudeheight')
                ->where($where)
                ->order('order_number asc,id asc')
                ->select();
            if ($list !== false) {
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['SUCCESS'],
                    'content' => $list
                );
            } else {
                //数据库连接错误	3
                $res = array(
                    'code' => $this->ERROR_CODE_COMMON['DB_ERROR']
                );
            }
        } else {
            // 错误信息返回码
            $res = $msg1;
        }
        return $res;
    }

    /**
     * 根据舱ID获取船的舱列表
     */
    public function getcabinlist($cabinid)
    {
        $shipid = $this
            ->field('shipid')
            ->where(array('id' => $cabinid))
            ->find();

        $cabinlist = $this
            ->field('id,cabinname')
            ->where(array('shipid' => $shipid['shipid']))
            ->order('id asc')
            ->select();
        return $cabinlist;
    }

    /**
     * 根据船ID获取各个舱的经验底量
     */
    public function get_cabins_base_volume($shipid)
    {
        $cabin_base_volume = $this
            ->field('id,base_volume as volume_sum,base_count,cabinname')
            ->where(array('shipid' => $shipid))
            ->select();

        foreach ($cabin_base_volume as $k=>$v){
            //获取平均值
            $cabin_base_volume[$k]['base_volume'] = $v['volume_sum']/($v['base_count']>0?$v['base_count']:1);
        }

        return $cabin_base_volume;
    }
}