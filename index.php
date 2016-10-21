<?php
require_once ('src/wechat.php');
$wechat = new wechat();
$act = isset($_GET['act'])?$_GET['act']:'index';
//$wechat->getLoginStatus();die;
switch ($act) {
  case 'index':
    //登录页
    $uuid = $wechat->getUuid();
    $qrcode = "https://login.weixin.qq.com/qrcode/{$uuid}?t=webwx";
    include_once('tpl/qrcode.php');
    break;
  case 'status':
    //获取登录状态
    $uuid = $_GET['uuid'];
    $res = $wechat->getLoginStatus($uuid);
    if($res == 201){
      //已扫描，待确认
      $data = array('status' => 1);
    }elseif (substr_count($res, 'http')) {
      //确认成功
      $data = array('status' => 2);
    }else{
      //待扫描
      $data = array('status' => 0);
    }
    $data['msg'] = $res;
    exit(json_encode($data));
    break;
  case 'cookies':
    //获取用户uin 和 sid
    $url = $_POST['url'];

    $wxinfo = $wechat->getCookies($url);
    exit(json_encode($wxinfo));
    break;
  case 'chat':
    //主聊天框页面
    include_once('tpl/chat.php');
    break;
  case 'init':
    //初使化微信信息
    $json_info = $wechat->initWebchat();
    //$userinfo = json_decode($json_info,true);
    //exit($json_info);
    break;
  default:
    # code...
    break;
}

?>