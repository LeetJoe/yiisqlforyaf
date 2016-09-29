<?php
/**
 * 微信支付API异常类
 */
class Api_Wxpay_WxPayException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
