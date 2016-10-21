<?php
/**
*Author @nango
*
*  封装的网页版微信通讯类
* 参考文章http://www.tanhao.me/talk/1466.html/
* 通讯步骤
*  1，微信服务器返回一个会话ID
*  2.通过会话ID获得二维码
*  3.轮询手机端是否已经扫描二维码并确认在Web端登录
*  4.访问登录地址，获得uin和sid
*  5.初使化微信信息
*  6.获得所有的好友列表
*  7.保持与服务器的信息同步
*  8.获得别人发来的消息
*  9.向用户发送消息
**/

class wechat {

	/**
	* uuid 微信服务器返回的会话id
	**/
	private $uuid = '';

	/**
	* loginUrl 扫描二维码并确认后返回的登录url
	**/
	private $loginUrl = '';
	
	/**
	 * 发起GET请求
	 *
	 * @access public
	 * @param string $url
	 * @return string
	 */
	function get($url)
	{
	  $ch = curl_init(); 
	  curl_setopt($ch, CURLOPT_URL, $url);
	  curl_setopt($ch, CURLOPT_HEADER, 0);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查  
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在 
	  curl_setopt($ch, CURLOPT_COOKIE, ''); //设置请求COOKIE
	  curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
	  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	  $output = curl_exec($ch);
	  curl_close($ch);
	  return $output;
	 }
	/**
	 * 发起POST请求
	 *
	 * @access public
	 * @param string $url
	 * @param array $data
	 * @return string
	 */
	public function post($url, $data = '', $cookie = '')
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    if($cookie){
	    	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	    }
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, 0);
	    curl_setopt ($ch, CURLOPT_REFERER,'https://wx.qq.com/');
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    return $output;
	}
	 /**
	 * 获取当前时间戳精确到毫秒级
	 *
	 * @access private
	 * @return string
	 * 
	 **/
	 private function getMillisecond()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return (float)sprintf('%.0f',(floatval($usec)+floatval($sec))*1000);
	}

	/**
	* 获取微信服务器返回的一个会话ID
	* 
	* @access public
	* @return string
	**/
	public function getUuid(){
		$url = 'https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Fwx.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_='.$this->getMillisecond();
		$str = $this->get($url);
		preg_match('/"(.*?)"/',$str,$match);
		return $match[1]; 
	}

	/**
	* 通过会话ID获得二维码
	* @access public
	* @return string
	**/
	public function getQrcode($uuid){
		$url = 'https://login.weixin.qq.com/qrcode/'.$uuid.'?t=webwx';
		return "<img src='$url' />";
	}
	
	/**
	* 轮询手机端是否已经扫描二维码并确认在Web端登录
	* @access public
	* @param $uuid string 用户会话id
	* @return mixed
	**/
	public function getLoginStatus($uuid = ''){
		$url = sprintf("https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?uuid=%s&tip=1&_=%s", $uuid, $this->getMillisecond());
		$res = $this->get($url);
		preg_match('/=(.*?);/',$res,$match);
		if($match[1] == 200){
			//登陆成功
			preg_match('/redirect_uri="(.*?)";/',$res,$match2);
			return $match2[1];
		}
		return $match[1];
	}

	/**
	* 访问登录地址，获得uin和sid,并且保存cookies
	* @access public
	* @param $url string 登录地址
	* @return array
	**/
	public function getCookies($url){
       	$cookie_jar = dirname(__FILE__)."/pic.cookie";
       
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_HEADER,1);//如果你想把一个头包含在输出中，
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。设置为0是直接输出
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);//获取的cookie 保存到指定的 文件路径
        $content=curl_exec($ch);     
        if(curl_errno($ch)){
            echo 'Curl error: '.curl_error($ch);exit(); //这里是设置个错误信息的反馈
         }    

        if($content==false){
            echo "get_content_null";exit();
        }
        //正则匹配出wxuin、wxsid
        preg_match('/wxuin=(.*);/iU',$content,$uin); 
        preg_match('/wxsid=(.*);/iU',$content,$sid);
        session_start();
        $_SESSION['uin'] = $uin[1];
        $_SESSION['sid'] = $sid[1];
        $wxinfo = array(
        	'uin' => $uin[1],
        	'sid' => $sid[1]
        	);
        curl_close($ch);
        return $wxinfo;
	}

	/**
	* 登录成功，初始化微信信息
	* @access public
	* @param $uin string 用户uin
	* @param $sid string 用户sid
	* @return mixed
	**/
	public function initWebchat($uin = '', $sid = ''){
		$cookie_jar = dirname(__FILE__)."/pic.cookie";
		$url = sprintf("https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxinit?r=%s", $this->getMillisecond());
		if(!$uin || !$sid){
			 session_start();
			  $uin = $_SESSION['uin'];
        	  $sid = $_SESSION['sid'];
		}
		$data['BaseRequest'] = array(
			'Uin' => $uin,
			'Sid' => $sid,
			'Skey' => '',
			'DeviceID' => 'e189320295398756'
			);
		$res = $this->post($url, json_encode($data),$cookie_jar);
		print_r($res);
		return $res;
	}
}