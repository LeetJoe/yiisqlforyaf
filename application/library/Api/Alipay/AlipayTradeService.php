<?php
if (!defined("AOP_SDK_WORK_DIR"))
{
	define("AOP_SDK_WORK_DIR", "/tmp/");
}

if (!defined("AOP_SDK_DEV_MODE"))
{
	define("AOP_SDK_DEV_MODE", true);
}

# 下面是其它支付方式所依赖的类,后续扩展时另行整理引用
# require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'./../model/result/AlipayF2FPayResult.php';
# require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'../model/result/AlipayF2FQueryResult.php';
# require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'../model/result/AlipayF2FRefundResult.php';
# require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'../model/result/AlipayF2FPrecreateResult.php';

/**
 * 支付宝交易服务
 */
class Api_Alipay_AlipayTradeService {

	# 支付宝网关地址
	public $gateway_url = "https://openapi.alipay.com/gateway.do";

	# 异步通知回调地址
	public $notify_url;

	# 支付宝公钥地址
	public $alipay_public_key;

	# 商户私钥地址
	public $private_key;

	# 应用id
	public $appid;

	# 编码格式
	public $charset = "UTF-8";


	public $token = NULL;
	
	# 重试次数
	private $MaxQueryRetry;
	
	# 重试间隔
	private $QueryDuration;

	# 返回数据格式
	public $format = "json";


	function __construct(){
		$config = Config::ini('pay.ini', 'alipay');
		$this->gateway_url = $config['ali']['gateway_url'];
		$this->appid = $config['ali']['app_id'];
		$this->private_key = $config['ali']['merchant_private_key'];
		$this->alipay_public_key = $config['ali']['alipay_public_key'];
		$this->charset = $config['ali']['charset'];
		$this->MaxQueryRetry = $config['ali']['max_query_retry'];
		$this->QueryDuration = $config['ali']['query_duration'];
		$this->notify_url = $config['ali']['notify_url'];

		if(empty($this->appid)||trim($this->appid)==""){
			throw new Exception("appid should not be NULL!");
		}
		if(empty($this->private_key)||trim($this->private_key)==""){
			throw new Exception("private_key should not be NULL!");
		}
		if(empty($this->alipay_public_key)||trim($this->alipay_public_key)==""){
			throw new Exception("alipay_public_key should not be NULL!");
		}
		if(empty($this->charset)||trim($this->charset)==""){
			throw new Exception("charset should not be NULL!");
		}
		if(empty($this->QueryDuration)||trim($this->QueryDuration)==""){
			throw new Exception("QueryDuration should not be NULL!");
		}
		if(empty($this->gateway_url)||trim($this->gateway_url)==""){
			throw new Exception("gateway_url should not be NULL!");
		}
		if(empty($this->MaxQueryRetry)||trim($this->MaxQueryRetry)==""){
			throw new Exception("MaxQueryRetry should not be NULL!");
		}

	}
	function AlipayWapPayService($alipay_config) {
		$this->__construct($alipay_config);
	}

	/**
	 * 当面付2.0预下单(生成二维码,带轮询)
	 *
	 * @param $req Api_Alipay_AlipayTradePrecreateContentBuilder
	 *
	 * @return SimpleXmlElement
	 */
	public function qrPay($req) {

		$bizContent = $req->getBizContent();
		$this->writeLog($bizContent);

		$request = new Api_Alipay_AlipayTradePrecreateRequest();
		$request->setBizContent ( $bizContent );
		$request->setNotifyUrl ( \Helper\Http::getServerUrl() . $this->notify_url );

		# 首先调用支付api
		$response = $this->aopclientRequestExecute ( $request, NULL ,$req->getAppAuthToken() );
		$response = $response->alipay_trade_precreate_response;
		\Helper\Log::write('PAY', $response, 'pay');

		if(!empty($response)&&("10000"==$response->code)){
			return $response;
		}

		return array();
	}

	# 查询返回“支付成功”
	protected function querySuccess($queryResponse){
		return !empty($queryResponse)&&
				$queryResponse->code == "10000"&&
				($queryResponse->trade_status == "TRADE_SUCCESS"||
					$queryResponse->trade_status == "TRADE_FINISHED");
	}

	# 查询返回“交易关闭”
	protected function queryClose($queryResponse){
		return !empty($queryResponse)&&
		$queryResponse->code == "10000"&&
		$queryResponse->trade_status == "TRADE_CLOSED";
	}

	# 交易异常，或发生系统错误
	protected function tradeError($response){
		return empty($response)||
					$response->code == "20000";
	}

	/**
	 * 使用SDK执行提交页面接口请求
	 * @param Api_Alipay_AlipayTradePrecreateRequest $request
	 * @param string $token
	 * @param string $appAuthToken
	 * @return SimpleXmlElement $result
	 */
	private function aopclientRequestExecute($request, $token = NULL, $appAuthToken = NULL) {

		$aop = new Api_Alipay_AopClient ();
		$aop->gatewayUrl = $this->gateway_url;
		$aop->appId = $this->appid;
		# $aop->rsaPrivateKeyFilePath = $this->private_key;
		$aop->rsaPrivateKey = $this->private_key;
		# $aop->alipayPublicKey = $this->alipay_public_key;
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$aop->apiVersion = "1.0";
		$aop->postCharset = $this->charset;


		$aop->format=$this->format;
		# 开启页面信息输出
		$aop->debugInfo=true;
		$result = $aop->execute($request,$token,$appAuthToken);

		# 打开后，将url形式请求报文写入log文件
		# $this->writeLog("response: ".var_export($result,true));
		return $result;
	}

	function writeLog($text) {
		# $text=iconv("GBK", "UTF-8//IGNORE", $text);
		# $text = characet ( $text );
		# file_put_contents ( "log/log.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
		\Helper\Log::write('ALIPAYSDK', date ( "Y-m-d H:i:s" ) . "  " . $text, 'pay');
	}

	/** *利用google api生成二维码图片
	 * $content：二维码内容参数
	 * $size：生成二维码的尺寸，宽度和高度的值
	 * $lev：可选参数，纠错等级
	 * $margin：生成的二维码离边框的距离
	 */
	function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
		$content = urlencode($content);
		$image = '<img src="http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content.'"  widht="'.$size.'" height="'.$size.'" />';
		return $image;
	}

}