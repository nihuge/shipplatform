<?php

namespace Common\Model;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

require 'vendor/autoload.php';

/**
 * 短信验证码Model
 * */
class SmsVerifyCodeModel extends BaseModel
{
    /**
     * 自动验证
     */
    protected $_validate = array(
        // 不能为空
        array('ip', 'require', 'ip地址不能为空', 0),// 必须验证 不能为空
        array('phone', 'require', '手机号不能为空', 0),// 必须验证 不能为空
        array('time', 'require', '创建时间不能为空', 0),// 必须验证 不能为空
        array('dead_time', 'require', '失效时间不能为空', 0),// 必须验证 不能为空
        array('code', 'require', '验证码不能为空', 0),// 必须验证 不能为空
        // 在一个范围之内
        // array('type',array('月付','季付','年付'),'支付类型的范围不正确！',0,'in'), // 当值不为空的时候判断是否在一个范围内
        array('sended', array('1', '2'), '是否发送的范围不正确！', 0, 'in'), // 必须验证 判断是否在一个范围内
        array('usable', array('1', '2'), '是否失效的范围不正确！', 0, 'in'), // 必须验证 判断是否在一个范围内
        // 长度判断
        array('phone', '1,11', '联系电话长度不能超过11个字符', 0, 'length'),//必须验证
        array('ip', '1,39', 'IP地址长度不能超过39个字符', 0, 'length'),//必须验证
        array('code', '1,4', '验证码长度不能超过4字符', 0, 'length'),//必须验证
        // 判断是否为整数
        array('phone', 'integer', '电话号码不是整数', 0),
    );

    /**
     * 发送短信验证码
     * @param $phone
     * @return array
     * @throws ClientException
     */
    public function sendSms($phone)
    {
        $ip = getUserIp();//获取用户IP地址
//        $phone = sprintf("%11u", $phone); //过滤手机号


        if (preg_match('/^[\d]{11}$/', $phone)) {
//            $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
//            $Model->query("select * from think_user where status=1");
            $where1 = array(
                'ip' => $ip,
                'phone' => $phone,
                '_logic' => 'or',
            );
            $user_where = array(
                'phone' => $phone
            );
            $user = new \Common\Model\UserModel();
            $user_count = $user->where($user_where)->count();
            if ($user_count == 0) {
                $where = array(
                    'usable' => 2,
                    'sended' => 2,
                    '_string' => 'dead_time > now()',
                    '_complex' => $where1
                );
                $sms_result = $this
                    ->field('time')
                    ->where($where)
                    ->order('time desc')
                    ->find();
                $sms_config = C('SMS_CONFIG');
                if (time() - strtotime($sms_result['time']) > $sms_config['FREQUENCY_LIMIT']) {
                    $verfify_code = substr(uniqid(), -4);
                    $send_msg = array(
                        'code' => $verfify_code
                    );
                    AlibabaCloud::accessKeyClient($sms_config['KEYID'], $sms_config['KEYSECRET'])
                        ->regionId($sms_config['REGIONID'])
                        ->asDefaultClient();
                    try {
                        $result = AlibabaCloud::rpc()
                            ->product('Dysmsapi')
                            // ->scheme('https') // https | http
                            ->version('2017-05-25')
                            ->action('SendSms')
                            ->method('POST')
                            ->host('dysmsapi.aliyuncs.com')
                            ->options(array(
                                'query' => array(
                                    'RegionId' => $sms_config['REGIONID'],
                                    'PhoneNumbers' => $phone,
                                    'SignName' => $sms_config['SIGNNAME'],
                                    'TemplateCode' => $sms_config['TEMPLATECODE'],
                                    'TemplateParam' => jsonreturn($send_msg),
                                ),
                            ))
                            ->request();
                        $result = $result->toArray();
                        if ($result['Code'] == 'OK') {
                            $sms_data = array(
                                'phone' => $phone,
                                'ip' => $ip,
                                'time' => date('Y-m-d H:i:s', time()),
                                'code' => $send_msg['code'],
                                'dead_time' => date('Y-m-d H:i:s', strtotime($sms_config['EXPIRE_TIME'])), //一小时有效期
                                'sended' => 2, //已发送
                                'usable' => 2, //已生效
                            );
                            $model_result = $this->add($sms_data);
                            if ($model_result !== false) {
                                $res = array('code' => $this->ERROR_CODE_COMMON['SUCCESS']);
                            } else {
                                //数据库错误 3
                                $res = array('code' => $this->ERROR_CODE_COMMON['DB_ERROR']);
                            }
                        } else {
                            $res = array('code' => $this->ERROR_CODE_USER['SMS_SEND_FALL'], 'error' => $result['Message']);
                        }
                    } catch (ClientException $e) {
                        $res = array('code' => $this->ERROR_CODE_USER['SMS_SEND_FALL'], 'msg' => $e->getErrorMessage() . PHP_EOL);
                    } catch (ServerException $e) {
                        $res = array('code' => $this->ERROR_CODE_USER['SMS_SEND_FALL'], 'msg' => $e->getErrorMessage() . PHP_EOL);
                    }
                } else {
                    $res = array('code' => $this->ERROR_CODE_USER['SMS_REQUEST_OFTEN']);
                }
            } else {
                $res = array('code'=>$this->ERROR_CODE_USER['PHONE_REPEAT']);
            }
        } else {
            $res = array('code' => $this->ERROR_CODE_USER['PHONE_ERROR']);
        }
        return $res;
    }

    /**
     * 检测短信验证码
     * @param $phone
     * @param $code
     * @return array
     */
    public function checkVerifyCode($phone, $code)
    {
        if (preg_match('/^[\d]{11}$/', $phone)) {
            $where = array(
                'usable' => 2,
                'sended' => 2,
                '_string' => 'dead_time > now()',
                'phone' => $phone
            );

            $sms_result = $this
                ->field('code')
                ->where($where)
                ->order('time desc')
                ->find();
            //无视大小写
            if (strtoupper($sms_result['code']) == strtoupper($code)) {
                $res = array('code' => $this->ERROR_CODE_COMMON['SUCCESS']);
            } else {
                //验证码错误 1020
                $res = array('code' => $this->ERROR_CODE_USER['VERIFY_CODE_ERROR']);
            }
        } else {
            //手机号格式错误 1017
            $res = array('code' => $this->ERROR_CODE_USER['PHONE_ERROR']);
        }
        return $res;
    }

}