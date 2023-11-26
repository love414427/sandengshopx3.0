<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace payment;

/**
 * 支付宝扫码支付
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2018-09-19
 * @desc    description
 */
class SandQrcode
{
    // 插件配置参数
    private $config;

    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-17
     * @desc    description
     * @param   [array]           $params [输入参数（支付配置参数）]
     */
    public function __construct($params = [])
    {
        $this->config = $params;
    }

    /**
     * 配置信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     */
    public function Config()
    {
        // 基础信息
        $base = [
            'name'          => '杉德云闪付扫码支付',  // 插件名称
            'version'       => '1.0.0',  // 插件版本
            'apply_version' => '不限',  // 适用系统版本描述
            'apply_terminal'=> ['pc', 'h5', 'app', 'alipay', 'weixin', 'baidu'], // 适用终端 默认全部 ['pc', 'h5', 'app', 'alipay', 'weixin', 'baidu']
            'desc'          => '杉德云闪付支付、适用web端',  // 插件描述（支持html）
            'author'        => 'Devil',  // 开发者
            'author_url'    => 'http://shopxo.net/',  // 开发者主页
        ];

        // 配置信息
        $element = [
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'appid',
                'placeholder'   => 'appid',
                'title'         => 'appid',
                'is_required'   => 0,
                'message'       => '请填写应用appid',
            ],
            [
                'element'       => 'textarea',
                'name'          => 'rsa_public',
                'placeholder'   => '应用公钥',
                'title'         => '应用公钥',
                'is_required'   => 0,
                'rows'          => 6,
                'message'       => '请填写应用公钥',
            ],
            [
                'element'       => 'textarea',
                'name'          => 'rsa_private',
                'placeholder'   => '应用私钥',
                'title'         => '应用私钥',
                'is_required'   => 0,
                'rows'          => 6,
                'message'       => '请填写应用私钥',
            ],
            [
                'element'       => 'textarea',
                'name'          => 'out_rsa_public',
                'placeholder'   => '支付宝公钥',
                'title'         => '支付宝公钥',
                'is_required'   => 0,
                'rows'          => 6,
                'message'       => '请填写支付宝公钥',
            ],
        ];

        return [
            'base'      => $base,
            'element'   => $element,
        ];
    }

    /**
     * 支付入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Pay($params = [])
    {
        
        // 参数
        if(empty($params))
        {
            return DataReturn('参数不能为空', -1);
        }
        
        // 配置信息
        if(empty($this->config) || empty($this->config['appid']) || empty($this->config['rsa_public']) || empty($this->config['rsa_private'])  )
        {
            return DataReturn('支付缺少配置', -1);
        }

      $native = array(
          "pay_memberid" => $this->config['appid'],
          "pay_orderid" => $params['order_no'],
          "pay_amount" => $params['total_price'],
          "pay_applydate" => date("Y-m-d H:i:s"),
          "pay_bankcode" => $this->config['rsa_private'],
          "pay_notifyurl" => $params['notify_url'],
          "pay_callbackurl" => $params['call_back_url'],
      );
      ksort($native);
      $md5str = "";
      foreach ($native as $key => $val) {
          $md5str = $md5str . $key . "=" . $val . "&";
      }
      
      $sign = strtoupper(md5($md5str . "key=fangyuan" . $this->config['rsa_public']));
      $native["pay_md5sign"] = $sign;
     // $native['pay_attach'] = $attach;
      $native['pay_productname'] =$params['name'];
      	 $native['pay_isJson']=1;
       
       	$tjurl=  'http://adm.qqdgxg001.top/Pay_Index_createOrder.html';
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, $tjurl);
              curl_setopt($ch, CURLOPT_POST, 1);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              
             
              curl_setopt($ch, CURLOPT_POSTFIELDS,  ($native));
              //curl_setopt($ch, CURLOPT_HEADER, true);
              $curl_result = curl_exec($ch);
            
             $this-> mylog($curl_result);
              curl_close($ch);
      $json = json_decode($curl_result);
       
      
       
      		 if($json->code==200)
      		  {
      			   return DataReturn('成功', 0,$json->url);
      			  
      		 }else{
      		 	
      			 return DataReturn($json->msg, -1000);
      			
      		 };
        return DataReturn( '处理失败', -1000);
    }
public function mylog($logg)
{

 
   $years = date('Y-m');
        //设置路径目录信息
        $url = './Runtime/log/'.$years.'_'.date('YmdH').'_pay_request_log.log';
        $dir_name=dirname($url);
        //目录不存在就创建
        if(!file_exists($dir_name))
        {
            //iconv防止中文名乱码
            $res = mkdir(iconv("UTF-8", "GBK", $dir_name),0777,true);
        }
        $fp = fopen($url,"a");//打开文件资源通道 不存在则自动创建
         
  fwrite($fp, date("Y-m-d H:i:s").var_export( 	$logg,true)."\r\n");//写入文件
 
  
  fclose($fp);//关闭资源通道 
  
}
    /**
     * 订单自动关闭的时间
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-24
     * @desc    description
     */
    public function OrderAutoCloseTime()
    {
        return intval(MyC('common_order_close_limit_time', 30, true)).'m';
    }

    /**
     * 支付回调处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
       public function Respond2($params = [])
    {
        return DataReturn('处理成功', 0, $params);
    }
    public function Respond($params = [])
    {
        $data = empty($_POST) ? $_GET :  array_merge($_GET, $_POST);
         $this-> mylog($data);
       $returnArray = array( // 返回字段
                 "memberid" => $data["memberid"], // 商户ID
                 "orderid" =>  $data["orderid"], // 订单号
                 "amount" =>  $data["amount"], // 交易金额
                 "datetime" =>  $data["datetime"], // 交易时间
                 "transaction_id" =>  $data["transaction_id"], // 支付流水号
                 "returncode" => $data["returncode"],
             );
           
             
            
             ksort($returnArray);
             reset($returnArray);
             $md5str = "";
             foreach ($returnArray as $key => $val) {
                 $md5str = $md5str . $key . "=" . $val . "&";
             }
       		  
             $sign = strtoupper(md5($md5str . "key=fangyuan" . $this->config['rsa_public']));
            if ($sign == $_REQUEST["sign"]) 
             {
                  if ($_REQUEST["returncode"] == "00") 
                 {
                     
                    	return DataReturn('OK', 0, $this->ReturnData($data));
                 }
             } 
        
         
            return DataReturn('签名校验失败', -100);
               

    }

    /**
     * [ReturnData 返回数据统一格式]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2018-10-06T16:54:24+0800
     * @param    [array]                   $data [返回数据]
     */
    private function ReturnData($data)
    {
       
        // 返回数据固定基础参数
       $data['trade_no']       = $data['transaction_id'];        // 支付平台 - 订单号
        $data['buyer_user']     = '';              // 支付平台 - 用户
        $data['out_trade_no']   = $data['orderid'];    // 本系统发起支付的 - 订单号
        $data['subject']        = '';                 // 本系统发起支付的 - 商品名称
        $data['pay_price']      = $data['amount'];               // 本系统发起支付的 - 总价

        
        return $data;
    }

    /**
     * 退款处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Refund($params = [])
    {
        // 参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_no',
                'error_msg'         => '订单号不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'trade_no',
                'error_msg'         => '交易平台订单号不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'refund_price',
                'error_msg'         => '退款金额不能为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 退款原因
        $refund_reason = empty($params['refund_reason']) ? $params['order_no'].'订单退款'.$params['refund_price'].'元' : $params['refund_reason'];

        // 退款参数
        $parameter = [
            'app_id'                => $this->config['appid'],
            'method'                => 'alipay.trade.refund',
            'format'                => 'JSON',
            'charset'               => 'utf-8',
            'sign_type'             => 'RSA2',
            'timestamp'             => date('Y-m-d H:i:s'),
            'version'               => '1.0',
        ];
        $biz_content = [
            'out_request_no'        => $params['order_no'].'JE'.str_replace('.', '', $params['refund_price']),
            'out_trade_no'          => $params['order_no'],
            'trade_no'              => $params['trade_no'],
            'refund_amount'         => (string) $params['refund_price'],
            'refund_reason'         => $refund_reason,
        ];
        $parameter['biz_content'] = json_encode($biz_content, JSON_UNESCAPED_UNICODE);

        // 生成签名参数+签名
        $params = $this->GetParamSign($parameter);
        $parameter['sign'] = $this->MyRsaSign($params['value']);

        // 执行请求
        $res = $this->HttpRequest('https://openapi.alipay.com/gateway.do', $parameter);
        if($res['code'] != 0)
        {
            return $res;
        }
        $key = str_replace('.', '_', $parameter['method']).'_response';
        $result = $res['data'][$key];

        // 状态
        if(isset($result['code']) && $result['code'] == 10000)
        {
            // 验证签名
            if(!$this->SyncRsaVerify($result, $res['data']['sign']))
            {
                return DataReturn('签名验证错误', -1);
            }

            // 统一返回格式
            $data = [
                'out_trade_no'  => isset($result['out_trade_no']) ? $result['out_trade_no'] : '',
                'trade_no'      => isset($result['trade_no']) ? $result['trade_no'] : '',
                'buyer_user'    => isset($result['buyer_user_id']) ? $result['buyer_user_id'] : '',
                'refund_price'  => isset($result['refund_fee']) ? $result['refund_fee'] : 0.00,
                'return_params' => $result,
            ];
            return DataReturn('退款成功', 0, $data);
        }

        // 直接返回支付信息
        $msg = empty($result['sub_msg']) ? (empty($result['msg']) ? '支付失败' : $result['msg']) : $result['sub_msg'];
        $code = empty($result['sub_code']) ? (empty($result['code']) ? '支付失败' : $result['code']) : $result['sub_code'];
    }

    /**
     * [GetParamSign 生成参数和签名]
     * @param  [array] $data   [待生成的参数]
     * @return [array]         [生成好的参数和签名]
     */
    private function GetParamSign($data)
    {
        $param = '';
        $sign  = '';
        ksort($data);

        foreach($data AS $key => $val)
        {
            $param .= "$key=" .urlencode($val). "&";
            $sign  .= "$key=$val&";
        }

        $result = array(
            'param' =>  substr($param, 0, -1),
            'value' =>  substr($sign, 0, -1),
        );
        return $result;
    }

    /**
     * [HttpRequest 网络请求]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-25T09:10:46+0800
     * @param    [string]          $url  [请求url]
     * @param    [array]           $data [发送数据]
     * @return   [mixed]                 [请求返回数据]
     */
    private function HttpRequest($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $body_string = '';
        if(is_array($data) && 0 < count($data))
        {
            foreach($data as $k => $v)
            {
                $body_string .= $k.'='.urlencode($v).'&';
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body_string);
        }
        $headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $reponse = curl_exec($ch);
        if(curl_errno($ch))
        {
            return DataReturn(curl_error($ch), -1);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if(200 !== $httpStatusCode)
            {
                return DataReturn('http请求错误['.$httpStatusCode.']', -1);
            }
        }
        curl_close($ch);
        return DataReturn('success', 0, json_decode($reponse, true));
    }

    /**
     * [MyRsaSign 签名字符串]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-24T08:38:28+0800
     * @param    [string]                   $prestr [需要签名的字符串]
     * @return   [string]                           [签名结果]
     */
    private function MyRsaSign($prestr)
    {
        if(stripos($this->config['rsa_private'], '-----') === false)
        {
            $res = "-----BEGIN RSA PRIVATE KEY-----\n";
            $res .= wordwrap($this->config['rsa_private'], 64, "\n", true);
            $res .= "\n-----END RSA PRIVATE KEY-----";
        } else {
            $res = $this->config['rsa_private'];
        }
        return openssl_sign($prestr, $sign, $res, OPENSSL_ALGO_SHA256) ? base64_encode($sign) : null;
    }

    /**
     * [MyRsaDecrypt RSA解密]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-24T09:12:06+0800
     * @param    [string]                   $content [需要解密的内容，密文]
     * @return   [string]                            [解密后内容，明文]
     */
    private function MyRsaDecrypt($content)
    {
        if(stripos($this->config['rsa_public'], '-----') === false)
        {
            $res = "-----BEGIN PUBLIC KEY-----\n";
            $res .= wordwrap($this->config['rsa_public'], 64, "\n", true);
            $res .= "\n-----END PUBLIC KEY-----";
        } else {
            $res = $this->config['rsa_public'];
        }
        $res = openssl_get_privatekey($res);
        $content = base64_decode($content);
        $result  = '';
        for($i=0; $i<strlen($content)/128; $i++)
        {
            $data = substr($content, $i * 128, 128);
            openssl_private_decrypt($data, $decrypt, $res, OPENSSL_ALGO_SHA256);
            $result .= $decrypt;
        }
        unset($res);
        return $result;
    }

    /**
     * [OutRsaVerify 支付宝验证签名]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-24T08:39:50+0800
     * @param    [string]                   $prestr [需要签名的字符串]
     * @param    [string]                   $sign   [签名结果]
     * @return   [boolean]                          [正确true, 错误false]
     */
    private function OutRsaVerify($prestr, $sign)
    {
        if(stripos($this->config['out_rsa_public'], '-----') === false)
        {
            $res = "-----BEGIN PUBLIC KEY-----\n";
            $res .= wordwrap($this->config['out_rsa_public'], 64, "\n", true);
            $res .= "\n-----END PUBLIC KEY-----";
        } else {
            $res = $this->config['out_rsa_public'];
        }
        $pkeyid = openssl_pkey_get_public($res);
        $sign = base64_decode($sign);
        if($pkeyid)
        {
            $verify = openssl_verify($prestr, $sign, $pkeyid, OPENSSL_ALGO_SHA256);
            unset($pkeyid);
        }
        return (isset($verify) && $verify == 1) ? true : false;
    }

     /**
     * [SyncRsaVerify 同步返回签名验证]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-25T13:13:39+0800
     * @param    [array]                   $data [返回数据]
     * @param    [string]                  $sign [签名]
     */
    private function SyncRsaVerify($data, $sign)
    {
        $string = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this->OutRsaVerify($string, $sign);
    }
}
?>