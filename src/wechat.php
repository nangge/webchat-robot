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
	* toLingUrl 图灵机器人api
	**/
	private $tlApi = 'http://www.tuling123.com/openapi/api';
	private $tlAppkey = '820176b52352471a943d73a8c304ad32';
	
	/**
	 * 发起GET请求
	 *
	 * @access public
	 * @param string $url
	 * @return string
	 */
	function get($url = '', $cookie = '')
	{
	  $ch = curl_init(); 
	  curl_setopt($ch, CURLOPT_URL, $url);
	  curl_setopt($ch, CURLOPT_HEADER, 0);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查  
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在 
	  if($cookie){
	    	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	    	curl_setopt ($ch, CURLOPT_REFERER,'https://wx.qq.com');
	    }
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
	public function post($url, $data = '', $cookie = '', $type = 0)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    if($cookie){
	    	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	    	curl_setopt ($ch, CURLOPT_REFERER,'https://wx.qq.com');
	    }
	    if($type){
	    	$header = array(
            'Content-Type: application/json',
        	);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    }
	    
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, 0);
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
		$_SESSION['uuid'] = $match[1];
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
		$url = sprintf("https://login.wx2.qq.com/cgi-bin/mmwebwx-bin/login?uuid=%s&tip=1&_=%s", $uuid, $this->getMillisecond());
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
       	$cookie_jar = dirname(__FILE__)."/".$_SESSION['uuid'].".cookie";
       
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
        preg_match('/wxuin=;/iU',$content,$uin); 
        preg_match('/wxsid=(.*);/iU',$content,$sid);
        preg_match('/webwx_data_ticket=(.*);/iU',$content,$webwx);
        //@TODO将wxuin、wxsid、webwx_data_ticket存入cookies，以便获取微信头像----暂无效 
        /*if(preg_match_all('/Set-Cookie:[\s]+([^=]+)=([^;]+)/i', $content,$match)) {
		  foreach ($match[1] as $key => $cookieKey ) {
		    setcookie($cookieKey,$match[2][$key],'36000','','.wx.qq.com');
		  }
		}*/
        //将wxuin、wxsid、webwx_data_ticket存入session
        
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
		$cookie_jar = dirname(__FILE__)."/".$_SESSION['uuid'].".cookie";
		$url = sprintf("https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxinit?r=%s", $this->getMillisecond());
		
		if(!$uin || !$sid){
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
		//将登陆用户username、nickname存入session中
		$user = json_decode($res, true);
		$_SESSION['username'] = $user['User']['UserName'];
		$_SESSION['nickname'] = $user['User']['NickName'];

		return $res;
	}

	/**
	* 获取全部联系人
	* @access public
	* @param $uin string 用户uin
	* @param $sid string 用户sid
	* @return mixed
	**/
	public function getContact($uin = '', $sid = ''){
		$cookie_jar = dirname(__FILE__)."/".$_SESSION['uuid'].".cookie";
		$url = sprintf("https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxgetcontact?lang=zh_CN&r=%s&seq=0", $this->getMillisecond());
		if(!$uin || !$sid){
			 
			  $uin = $_SESSION['uin'];
        	  $sid = $_SESSION['sid'];
		}
		
		$res = $this->post($url, '{}',$cookie_jar);
		return $res;
	}


	/**
	* 登录成功，保持与服务器的信息同步，获取是否有推送消息等
	* @access public
	* @param $synckey string 
	* @return mixed
	**/
	public function wxsync($synckey){
		
	    $uin = $_SESSION['uin'];
	    $sid = $_SESSION['sid'];
		$cookie_jar = dirname(__FILE__)."/".$_SESSION['uuid'].".cookie";
		$url = sprintf("https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxsync?sid=%s", $sid);
		
		$data['BaseRequest'] = array(
			'Uin' => $uin,
			'Sid' => $sid,
			'Skey' => '',
			'DeviceID' => 'e189320295398756'
			);
		$data['SyncKey'] = json_decode($synckey);
		$data['rr'] = time();
		$res = $this->post($url, json_encode($data),$cookie_jar);
		return $res;
	}

	/**
	* 发送消息
	* @access public
	* @param $toUsername string 
	* @return mixed
	**/
	public function sendMessage($toUsername = '', $content = ''){		
	    $uin = $_SESSION['uin'];
	    $sid = $_SESSION['sid'];
		$cookie = dirname(__FILE__)."/".$_SESSION['uuid'].".cookie";
		$url = sprintf("https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxsendmsg?sid=%s&r=%s",$sid,$this->getMillisecond());
		
		$data['BaseRequest'] = array(
			'Uin' => $uin,
			'Sid' => $sid,
			'Skey' => "",
			'DeviceID' => "e287276317582836"
			);
		$data['Msg'] = array(
			'ClientMsgId' => $this->getMillisecond(),
			'Content' => $content,
			'FromUserName' => $_SESSION['username'],
			'LocalID' => $this->getMillisecond(),
			'ToUserName' => $toUsername,
			'Type' => 1
			);
		$data['Scene'] = 0;
		//json_encode   JSON_UNESCAPED_UNICODE防止将汉字转义为unicode字符
		$res = $this->post($url, json_encode($data,JSON_UNESCAPED_UNICODE),$cookie);
		return $res;
	}

	/**
	* 获取头像
	* @access public
	* @param $uri string 头像地址
	* @return mixed
	**/
	public function  getAvatar($uri = ''){
	    $cookie = dirname(__FILE__)."/".$_SESSION['uuid'].".cookie";
		$url = "https://wx.qq.com".$uri;
		$res = $this->get($url, $cookie);
		echo $res;
		}

	/**
	* 图灵机器人 =》文本
	* @access public
	* @param $toUsername string 
	* @return mixed
	**/
	public function sendMessageToTuling($content = ''){
		$data = array(
			'key' => $this->tlAppkey,
			'info' => $content,
			'userid' => '123456789'
			);
		$res = $this->post($this->tlApi,json_encode($data,JSON_UNESCAPED_UNICODE),'',1);
		$r = json_decode($res,true);
		//文本类
		if(isset($r['url'])){
			//存在链接则发送链接
			return $r['url'];
		}
		return $r['text'];

		
	}

	//转换为UTF-8
	public function characet($data){
	  if( !empty($data) ){
	    $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;
	    if( $fileType != 'UTF-8'){
	      $data = mb_convert_encoding($data ,'utf-8' , $fileType);
	    }
	  }
	  return $data;
	}
}