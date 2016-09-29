<?php

/**
 * 订单取消消息体构建类
 */
class Api_Alipay_AlipayTradeCancelContentBuilder extends Api_Alipay_ContentBuilder
{
    # 支付宝交易号,和商户订单号不能同时为空
    private $tradeNo;

    # 商户订单号,通过商户订单号撤销相关交易
    private $outTradeNo;

    private $bizContentarr = array();

    private $bizContent = NULL;

    public function getBizContent()
    {
        if(!empty($this->bizContentarr)){
            $this->bizContent = json_encode($this->bizContentarr,JSON_UNESCAPED_UNICODE);
        }
        return $this->bizContent;
    }

    public function getOutTradeNo()
    {
        return $this->outTradeNo;
    }

    public function setOutTradeNo($outTradeNo)
    {
        $this->outTradeNo = $outTradeNo;
        $this->bizContentarr['out_trade_no'] = $outTradeNo;
    }

    public function getTradeNo()
    {
        return $this->tradeNo;
    }

    public function setTradeNo($tradeNo)
    {
        $this->tradeNo = $tradeNo;
        $this->bizContentarr['trade_no'] = $tradeNo;
    }


}

?>