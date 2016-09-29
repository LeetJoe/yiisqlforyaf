<?php

class Pay
{
	const PAY_TYPE_WEIXIN = 1;
	const PAY_TYPE_ALIPAY = 2;

	static $payTypeDesc = array(
		self::PAY_TYPE_WEIXIN => '微信',
		self::PAY_TYPE_ALIPAY => '支付宝'
	);

	const SCAN_NOTIFY_PREFIX = 'biying_odntfy_';

	/**
	 * @desc 获取微信支付订单结果
	 *
	 * @param string $body string 商品描述
	 * @param string $tradeNum string 订单号
	 * @param string $totalFee int 订单金额
	 * @param string $goodsTag string 商品tag
	 * @param string $tradeType string 交易类型 JSAPI，NATIVE，APP
	 * @param string $productId int 商品id
	 * @param string $attach string 可选,附加描述
	 * @param int    $timeExpire 订单过期时长,默认10分钟
	 *
	 * @return array 成功时返回
	 */
	public static function getWxOrderResult($body, $tradeNum, $totalFee, $goodsTag, $tradeType, $productId, $attach = '', $timeExpire = 600) {
		# 获取微信配置
		$wxPayConfig = Config::ini('pay.ini', 'wxpay');
		$notifyUrl = $wxPayConfig['wx']['notify_url'];

		# 生成订单result
		$notify = new Api_Wxpay_NativePay();
		$input = new Api_Wxpay_WxPayUnifiedOrder();
		$input->SetBody($body);
		$input->SetAttach($attach);
		$input->SetOut_trade_no($tradeNum);
		$input->SetTotal_fee($totalFee);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + $timeExpire));
		$input->SetGoods_tag($goodsTag);
		$input->SetNotify_url($notifyUrl);
		$input->SetTrade_type($tradeType);
		$input->SetProduct_id($productId);
		return $notify->GetPayUrl($input);
	}

	/**
	 * @param string $subject
	 * @param string $body
	 * @param string $tradeNum
	 * @param int    $totalAmount
	 * @param int    $undiscountableAmount
	 * @param array $goodsDetailList
	 * @param string $timeExpire
	 *
	 * @return SimpleXmlElement
	 */
	public static function getAliOrderResult($subject, $body, $tradeNum, $totalAmount, $undiscountableAmount, $goodsDetailList, $timeExpire = "10m") {
		$aliConfig = Config::ini('pay.ini', 'alipay');
		$alipayStoreId = $aliConfig['ali']['alipay_store_id'];
		$storeId = $aliConfig['ali']['store_id'];

		# 系统商pid,作为系统商返佣数据提取的依据
		$providerId = $aliConfig['ali']['provider_id'];
		$extendParams = new Api_Alipay_ExtendParams();
		$extendParams->setSysServiceProviderId($providerId);
		$extendParamsArr = $extendParams->getExtendParams();

		# 第三方应用授权令牌,商户授权系统商开发模式下使用
		$appAuthToken = "";

		# 创建请求builder，设置请求参数
		$qrPayRequestBuilder = new Api_Alipay_AlipayTradePrecreateContentBuilder();
		$qrPayRequestBuilder->setOutTradeNo($tradeNum);
		$qrPayRequestBuilder->setTotalAmount($totalAmount);
		$qrPayRequestBuilder->setTimeExpress($timeExpire);
		$qrPayRequestBuilder->setSubject($subject);
		$qrPayRequestBuilder->setBody($body);
		$qrPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
		$qrPayRequestBuilder->setExtendParams($extendParamsArr);
		$qrPayRequestBuilder->setGoodsDetailList($goodsDetailList);
		$qrPayRequestBuilder->setStoreId($storeId);
		# fixed
		$qrPayRequestBuilder->setOperatorId(0);
		$qrPayRequestBuilder->setAlipayStoreId($alipayStoreId);

		$qrPayRequestBuilder->setAppAuthToken($appAuthToken);
		# 调用qrPay方法获取当面付应答
		$qrPay = new Api_Alipay_AlipayTradeService();
		return $qrPay->qrPay($qrPayRequestBuilder);
	}
}