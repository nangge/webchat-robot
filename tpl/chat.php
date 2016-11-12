<!DOCTYPE html>
<html>
	<meta charset="UTF-8">
	<title>web chat</title>
	<link href="static/chat.css" rel='stylesheet' type='text/css' />
	<script src="static/jquery-1.7.2.js"></script>
</head>
<body>

<div id="chat">
    <div class="sidebar">
        <div class="m-card">
            <footer>
                <input class="search" placeholder="查找好友">
            </footer>
        </div>
        <div class="m-list" style="overflow-y: scroll;height: calc(100% - 10pc);">
        	<ul>
        	</ul>
        </div>
    </div>
    <div class="main">
    <h3 align='center' class="to-user" username=""></h3>
        <div class="m-message">
            <ul>
                
            </ul>
        </div>
        <!--send-->
        <div class="m-text">
            <textarea placeholder="按 Ctrl + Enter 发送" class="input"></textarea>
        </div>
    </div>
</div>
</body>
<script type="text/javascript">
$(function(){
	if(navigator.userAgent.match(/(iPhone|iPod|Android|ios)/i)){
		alert('暂不支持移动端访问，请移步PC端！');
		return false;
	}
	var synckey = '';
	var mname = '';
	var mnickname = '';
	//微信初始化
	$.ajax({
			url : "index.php?act=init",
			datatype : 'json',
			type : 'post',
			data : {},
			success : function(data){

				var res = JSON.parse(data);

				//将synckey存入本地缓存，后续步骤需要
				synckey =  JSON.stringify(res.SyncKey);//json 串形式存入
				sessionStorage.synckey = synckey;
				muname = res.User.UserName;
				sessionStorage.username = muname;
				mnickname = res.User.NickName;
				sessionStorage.nickname = mnickname;
				//登陆用户信息
				var ustr = '<header>'
                +'<img class="avatar" width="40" height="40" alt="Coffce" src="static/images/nango.jpg">'
                +'<p class="name">'+ res.User.NickName +'</p>'
	            +'</header>';
	            $(".m-card").prepend(ustr);
				//遍历初始化返回的好友和公众号
				var userlist = res.ContactList;	
				var str = '';
				for (var key in userlist) {
					var img = 'http://wx.qq.com' + userlist[key].HeadImgUrl;
					str += '<li class="active" username="'+ userlist[key].UserName +'">'
	            		+'<img class="avatar" width="30" height="30"  src="http://lorempixel.com/30/30/" />'
	            		+'<p class="name">'+ userlist[key].NickName +'</p>'
	        			+'</li>';
				}
				$(".m-list ul").append(str);
			    //滚动到底部
			    $(".m-message").scrollTop($('.m-message ul')[0].scrollHeight);
			},
			error : function(data){
				console.log(data);
			}
		});
	//初始化 结束

	//获取所有好友列表
	$.ajax({
		url : "index.php?act=users",
		datatype : 'json',
		type : 'post',
		data : {},
		success : function(data){
			var res = JSON.parse(data);
			var users = {};//存储username =》 nickname
			//遍历初始化返回的好友和公众号
			var userlist = res.MemberList;	
			var str = '';
			for (var key in userlist) {
				var img = 'http://wx.qq.com' + userlist[key].HeadImgUrl;
				var uname = userlist[key].UserName;
				var nickname = userlist[key].NickName;
				str += '<li class="active" username="'+ uname +'">'
            		+'<img class="avatar" width="30" height="30"  src="http://lorempixel.com/30/30/" />'
            		+'<p class="name">'+ nickname +'</p>'
        			+'</li>';
        		users[uname] = nickname;
			}
			//把登陆用户的信息也附加上
			users[mname] = mnickname;

			sessionStorage.users = JSON.stringify(users);
			$(".m-list ul").append(str);
		    //滚动到底部
		    //$(".m-message").scrollTop($('.m-message ul')[0].scrollHeight);
		},
		error : function(data){
			console.log(data);
		}
		});
	//获取好友列表结束


	//var sync = setInterval("syncWx()",1000);
	sync();
	function sync(){
		//syncWx = function (){
		//同步服务器信息，轮训查询是否有新消息等
		if(!synckey){
			synckey = sessionStorage.synckey;
		}
		$.ajax({
			url : "index.php?act=sync",
			datatype : 'json',
			type : 'post',
			data : {synckey: synckey},
			success : function(data){
				var res = JSON.parse(data);
				
				//与服务器同步一次synckey就可能 不相同一次，所以每次同步完都将更新key
				//将synckey存入本地缓存，后续步骤需要
				synckey =  JSON.stringify(res.SyncKey);//json 串形式存入
				//sessionStorage.synckey = synckey;
				if(res.BaseResponse.Ret != 0){
					alert('与微信服务器通讯出错，请刷新重试或重新扫码登陆！');
					//clearInterval(sync);
				}else if (res.AddMsgCount) {
					var str = '';
					var messagelist = res.AddMsgList;
					var users = JSON.parse(sessionStorage.users);
					for (var key in messagelist) {
						var fname = messagelist[key].FromUserName;
						str += '<li>'
	                    	+'<p class="time"><span></span></p>'
	                    	+'<div class="main">'
	                        +'<img class="avatar" width="30" height="30" src="static/images/nango.jpg">'
	                        +'<div class="nick">'+ users[fname] +'</div>'
	                        +'<div class="text">'+ messagelist[key].Content +'</div>'
	                    	+'</div>'
	                	+'</li>';

	                	//机器人自动回复，不需要注释掉即可
	                	$.post('index.php?act=tuling',{content:messagelist[key].Content,toUsername:messagelist[key].FromUserName},function(data){
	                		if(data.status == 0){return ;}
							if(data.BaseResponse.Ret == 0){
								var str = '<li>'
		                    	+'<p class="time"><span></span></p>'
		                    	+'<div class="main">'
		                        +'<img class="avatar" width="30" height="30" src="http://lorempixel.com/30/30/">'
		                        +'<div class="nick">机器人</div>'
		                        +'<div class="text">'+ data.tlc +'</div>'
		                    	+'</div>'
			                	+'</li>';
								$(".m-message ul").append(str);
							    //滚动到底部
							    $(".m-message").scrollTop($('.m-message ul')[0].scrollHeight);
								}
					  			},'json')
	                	//机器人回复结束

					}
					//for end
					$(".m-message ul").append(str);
				    //滚动到底部
				    $(".m-message").scrollTop($('.m-message ul')[0].scrollHeight);
				    sync();
				}
				
			},
			error : function(data){
				console.log(data);
			}
		})

	//}
	}
	

	//好友列表点击事件
	$(".m-list ul").on('click','li',function(){
		var username = $(this).attr('username');
		var nickname = $(this).children('p.name').text();
 		$('.to-user').attr('username',username);
 		$('.to-user').text(nickname);
	})
	//好友列表点击事件end

	//发送消息
	//发送消息
	$(".input").keypress(function(e) {
		//firefox enter code=13 ; chrome = 10		
		if (e.ctrlKey && (e.which == 13 || e.which == 10)){
			var text = $(".input").val();
			var toUsername = $('.to-user').attr('username');
			if(text == ''){
				alert('不能发送空内容！');
				return;
			}
			if(toUsername == ''){
				alert('请选择聊天对象！');
				return;
			}

			$.ajax({
				url : "index.php?act=send",
				datatype : 'json',
				type : 'post',
				data : {
					toUsername:toUsername,
					content:text
				},
				success : function(data){
					var res = JSON.parse(data);
					
					if(res.BaseResponse.Ret == 0){
						var str = '<li>'
                    	+'<p class="time"><span></span></p>'
                    	+'<div class="main">'
                        +'<img class="avatar" width="30" height="30" src="static/images/nango.jpg">'
                        +'<div class="nick">'+ sessionStorage.nickname +'</div>'
                        +'<div class="text">'+ text +'</div>'
                    	+'</div>'
	                	+'</li>';
						$(".m-message ul").append(str);
					    //滚动到底部
					    $(".m-message").scrollTop($('.m-message ul')[0].scrollHeight);
					}
					
				},
				error : function(data){
					console.log(data);
				}
			});
			//ws.send(content);
			$(".input").val('').focus();
		}
	});

	});

	</script>
</html>