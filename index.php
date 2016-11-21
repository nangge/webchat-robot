<?php
require_once ('src/wechat.php');
$wechat = new wechat();
$act = isset($_GET['act'])?$_GET['act']:'index';
session_start();
/*$res = $wechat->getLoginStatus();
print_r($res);die;*/
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
    exit($json_info);
    break;
  case 'users':
  //获取所有好友列表
    $users = $wechat->getContact();
    echo $users;
    break;
  case 'sync':
  //服务器同步
    $synckey = $_POST['synckey'];
    $message = $wechat->wxsync($synckey);
    
    exit($message);
    break;
  case 'send':
    $toUsername = $_POST['toUsername'];
    $content = $_POST['content'];

    $res = $wechat->sendMessage($toUsername, $content);
    exit($res);
    break;
  case 'avatar':
    $uri = $_GET['uri'];
    $res = $wechat->getAvatar($uri);
    header('Content-Type: image/jpeg');
    imagejpeg($res);
    break;
  case 'tuling':
    //图灵机器人接管消息
    $toUsername = $_POST['toUsername'];
    $content = $_POST['content'];
    if($toUsername != $_SESSION['username']){
      $mes = $wechat->sendMessageToTuling($content);
      $res = $wechat->sendMessage($toUsername, $mes);
      //拼接上机器人的回话
      $tlCon = json_decode($res,true);
      $tlCon['tlc'] = $mes;
      $tlCon['status'] = 1;
      exit(json_encode($tlCon));
    }
    exit(json_encode(array('status' => 0)));
    break;
  default:
    # code...
    break;
}

?>