<?php

/**
 *      This file is part of SuiNiPai.
 *      (author) thinfell <thinfell@qq.com>
 *		[SuiNiPai] Copyright (c) 2016 Qurui Inc. Code released under the MIT License.
 *      www.suinipai.com
 */

require_once(DISCUZ_ROOT."source/plugin/yinxingfei_recharge/alipay/Alipay.class.php");
require_once(DISCUZ_ROOT."source/plugin/yinxingfei_recharge/weixin/WeiXin.class.php");

class Recharge {

    private $para;
    private $api_gateway = 'http://server.suinipai.com/v1';
    private $api_check = 'http://server.suinipai.com/v1/verification';
    private $back_return_url = 'plugin.php?id=yinxingfei_recharge:return_url';
    private $weixin_check_url = 'plugin.php?id=yinxingfei_recharge:weixin_check';

    public function __construct($para)
    {
		$this->para = $para;
	}

    public function run()
    {
        global $_G;
        //获取设备信息
        $this->para['user_agent'] = $this->userAgent();

         //首先验证参数
        $checkResult = $this->check_para();

        if($checkResult == 'success'){
            switch ($_G['cache']['plugin']['yinxingfei_recharge']['signtype']) {
                case 0 :
                    $result = $this->selfSign();
                    break;
                case 1 :
                    $result = $this->exemptSign();
                    break;
                default :
                    $result = array(
                        'code' => 0,
                        'message' => 'signtype error',
                    );
            }
        }else{
            $result = array(
                'code' => 0,
                'message' => $checkResult,
            );
        }
        return $result;
    }

    private function check_para()
    {
        //基础参数验证
        if(!array_key_exists('out_trade_no', $this->para) || !isset($this->para['out_trade_no'])) {
            $result = 'missing out_trade_no';
        }elseif (!array_key_exists('fee', $this->para) || !isset($this->para['fee'])){
            $result = 'missing fee';
        }elseif (intval($this->para['fee']) < 1 ){
            $result = 'fee must be greater than 0';
        }elseif (!array_key_exists('subject', $this->para) || !isset($this->para['subject'])){
            $result = 'missing subject';
        }elseif (!array_key_exists('paytype', $this->para) || !isset($this->para['paytype'])){
            $result = 'missing paytype';
        }else{
            $result = 'success';
        }
        return $result;
    }

    private function selfSign()
    {
        switch ($this->para['paytype']) {
            case "alipay" :
                $result = $this->buildAlipay();
                break;
            case "weixin" :
                $result = $this->buildWeixin();
                break;
            default :
                $result = array(
                    'code' => 0,
                    'message' => 'PayType error',
                );
        }
        return $result;
    }

    private function exemptSign()
    {
        global $_G;
        $post_order = array(
            'user_agent' => $this->para['user_agent'],
            'orderid' => $this->para['out_trade_no'],
            'subject' => $this->para['subject'],
            'fee' => $this->para['fee'],
            'return_url' => urlencode($_G['siteurl'].$this->back_return_url),
            'notify_url' => urlencode($_G['siteurl'].'source/plugin/yinxingfei_recharge/notify_url.inc.php'),
            'paytype' => $this->para['paytype'],
            'optional' => serialize($this->para['optional']),
        );

        $timestamp = $this->microtime_float();
        $sign = $this->buildRequestSign($timestamp);
        $post_base = array(
            'partner' => $_G['cache']['plugin']['yinxingfei_recharge']['partner'],
            'timestamp' => $timestamp,
            'sign' => $sign,
        );

        $post_data = array_merge($post_base, $post_order);
        $result_data = $this->payPost($post_data);

        if($result_data->code == 200){
            $jsTimestamp = $this->microtime_float();
            $return_url = $_G['siteurl'].$this->back_return_url;
            $jsSign = $this->buildRequestSign($jsTimestamp);
            if($this->para['paytype'] == 'weixin' && $this->para['user_agent'] != 'weixin'){

                $javascript = <<<EOF
                
<script type="text/javascript">
    var jq = jQuery.noConflict();
    jq(document).ready(function(){
        window.setInterval(checktp, 3000);
    });
    function checktp(){
        jq.post(
            "{$this->api_check}",
            {
                orderid: '{$this->para['out_trade_no']}',
                partner: '{$_G['cache']['plugin']['yinxingfei_recharge']['partner']}',
                timestamp: '{$jsTimestamp}',
                sign: '{$jsSign}',
            },
            function(data,status){
                if( data.code == 200){
                    window.location.href="{$return_url}&fee={$this->para['fee']}&orderid={$this->para['out_trade_no']}";
                }
            },
            'json'
        );
    }
</script>

EOF;

                $return = array(
                    'code' => 200,
                    'user_agent' => $this->para['user_agent'],
                    'javascript' => $javascript,
                    'img' => $result_data->message,
                );
            }else{
                //$result_data->message = diconv($result_data->message, 'UTF-8', CHARSET);
                $return = array(
                    'code' => 200,
                    'user_agent' => $this->para['user_agent'],
                    'html' => $result_data->message,
                );
            }
        }else{
            $return = array(
                'code' => 0,
                'message' => $result_data->message,
            );
        }
        return $return;
    }

    private function buildRequestSign($timestamp)
    {
        global $_G;
        $sign = md5($_G['cache']['plugin']['yinxingfei_recharge']['partner'].$_G['cache']['plugin']['yinxingfei_recharge']['tkey'].$timestamp);
		return $sign;
	}

    private function buildWeixin()
    {
        global $_G;

        $Weixin = new WeiXin();
        $result_data = $Weixin->run($this->para);
        if($result_data['code'] == 200) {
            $jsTimestamp = $this->microtime_float();
            $return_url = $_G['siteurl'] . $this->back_return_url;
            $jsSign = md5($this->para['out_trade_no'] . $jsTimestamp);
            if ($this->para['paytype'] == 'weixin' && $this->para['user_agent'] != 'weixin') {

                $javascript = <<<EOF
                
<script type="text/javascript">
    var jq = jQuery.noConflict();
    jq(document).ready(function(){
        window.setInterval(checktp, 3000);
    });
    function checktp(){
        jq.post(
            "{$_G['siteurl']}{$this->weixin_check_url}",
            {
                orderid: '{$this->para['out_trade_no']}',
                timestamp: '{$jsTimestamp}',
                sign: '{$jsSign}',
            },
            function(data,status){
                if( data == 1){
                    window.location.href="{$return_url}&fee={$this->para['fee']}&orderid={$this->para['out_trade_no']}";
                }
            },
            'json'
        );
    }
</script>

EOF;

                $return = array(
                    'code' => 200,
                    'user_agent' => $this->para['user_agent'],
                    'javascript' => $javascript,
                    'img' => $result_data['message'],
                );
            }
        }else{
            $return = array(
                'code' => 0,
                'message' => $result_data['message'],
            );
        }
        return $return;
    }

    private function buildAlipay()
    {
        global $_G;
        $user_agent = $this->para['user_agent'];
        $out_trade_no = $this->para['out_trade_no'];
        $subject = $this->para['subject'];
        $total_fee =  $this->para['fee'] / 100;

        $alipay_config['partner']		= $_G['cache']['plugin']['yinxingfei_recharge']['ec_partner'];
        $alipay_config['seller_id']	= $alipay_config['partner'];
        $alipay_config['key']			= $_G['cache']['plugin']['yinxingfei_recharge']['ec_securitycode'];
        $alipay_config['notify_url'] = $_G['siteurl'].'source/plugin/yinxingfei_recharge/notify_url.inc.php';
        $alipay_config['return_url'] = $alipay_config['notify_url'];
        $alipay_config['sign_type']    = strtoupper('MD5');
        $alipay_config['input_charset']= CHARSET;
        $alipay_config['transport']    = 'http';
        $alipay_config['payment_type'] = "1";
        if($user_agent != 'pc'){
            $alipay_config['app_pay'] 			= "Y";
            $alipay_config['qr_pay_mode']		= "";
            $alipay_config['service'] 			= "alipay.wap.create.direct.pay.by.user";
        }else{
            $alipay_config['app_pay'] 			= "";
            $alipay_config['qr_pay_mode']		= "";
            $alipay_config['service'] 			= 'create_direct_pay_by_user';
        }
        $alipay_config['anti_phishing_key'] = "";
        $alipay_config['exter_invoke_ip'] = "";

        $parameter = array(
            "service"       => $alipay_config['service'],
            "partner"       => $alipay_config['partner'],
            "seller_id"  => $alipay_config['seller_id'],
            "payment_type"	=> $alipay_config['payment_type'],
            "notify_url"	=> $alipay_config['notify_url'],
            "return_url"	=> $alipay_config['return_url'],

            "anti_phishing_key"=>$alipay_config['anti_phishing_key'],
            "exter_invoke_ip"=>$alipay_config['exter_invoke_ip'],
            "out_trade_no"	=> $out_trade_no,
            "subject"	=> $subject,
            "total_fee"	=> $total_fee,
            "body"	=> '',
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
        );

        $alipaySubmit = new Alipay($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter);
        $return = array(
            'code' => 200,
            'user_agent' => $user_agent,
            'html' => $html_text,
        );
        return $return;
	}

    private function microtime_float()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    private function userAgent()
    {
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == true ) {//判断一下是否是微信内使用支付宝
            $user_agent = 'weixin';
        }elseif( strpos($_SERVER['HTTP_USER_AGENT'], 'Android') == true || strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') == true){
            $user_agent = 'wap';
        }else{
            $user_agent = 'pc';
        }
        return $user_agent;
    }

    private function payPost($post_data, $timeout = 5)
    {
        $url = $this->api_gateway;
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt ($ch, CURLOPT_HEADER, false);
        $return_result = curl_exec($ch);
        curl_close($ch);
        return json_decode($return_result);
    }
}
?>