<?php

/**
 * 刷卡支付实现类
 */
class Api_Wxpay_NativePay
{
	/**
	 * 
	 * 生成扫描支付URL,模式一
	 * @param Api_Wxpay_BizPayUrlInput $bizUrlInfo
	 */
	public function GetPrePayUrl($productId)
	{
		$biz = new Api_Wxpay_WxPayBizPayUrl();
		$biz->SetProduct_id($productId);
		$values = Api_Wxpay_WxPayApi::bizpayurl($biz);
		$url = "weixin://wxpay/bizpayurl?" . $this->ToUrlParams($values);
		return $url;
	}
	
	/**
	 * 
	 * 参数数组转换为url参数
	 * @param array $urlObj
	 */
	private function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			$buff .= $k . "=" . $v . "&";
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
	
	/**
	 * 
	 * 生成直接支付url，支付url有效期为2小时,模式二
	 *
	 * @param Api_Wxpay_WxPayUnifiedOrder $input
	 */
	public function GetPayUrl($input)
	{
		if($input->GetTrade_type() == "NATIVE")
		{
			$result = Api_Wxpay_WxPayApi::unifiedOrder($input);
			return $result;
		} else {
			return array();
		}
	}
}