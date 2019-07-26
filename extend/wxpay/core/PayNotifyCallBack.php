<?php

/**
 * @link http://blog.kunx.org/.
 * @copyright Copyright (c) 2016-11-23 
 * @license kunx-edu@qq.com.
 */
class PayNotifyCallBack extends WxPayNotify {

    //查询订单
    public function Queryorder($transaction_id) {
        $input  = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        Log::DEBUG("query:" . json_encode($result));
        if (array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS") {
            return true;
        }
        return false;
    }

    //重写回调函数
   public function NotifyProcess($data,&$msg)
    {
        Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();

        // 1.进行参数校验
        if(!array_key_exists("return_code", $data)
            ||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
            //TODO失败,不是支付成功的通知
            //如果有需要可以做失败时候的一些清理处理，并且做一些监控
            $msg = "return_code 异常";
            return false;
        }
        if(!array_key_exists("transaction_id", $data)){
            $msg = "未获取到transaction_id";
            return false;
        }

        // 2.进行签名验证
        try {
            $objData = new WxPayResults();
            $objData->FromArray($data);
            $checkResult = $objData->CheckSign();
            if($checkResult == false){
                //签名错误
                Log::ERROR("签名错误...");
                return false;
            }
        } catch(Exception $e) {
            Log::ERROR(json_encode($e->getMessage()));
        }

        // 3.处理业务逻辑：查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }
        return true;


    }

}
