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
            <header>
                <img class="avatar" width="40" height="40" alt="Coffce" src="static/images/nango.jpg">
                <p class="name">nango</p>
            </header>
            <footer>
                <input class="search" placeholder="search room...//TODO">
            </footer>
        </div>
        <!--v-component-->
        <div class="m-list">
            <ul>
                <li class="active">
                    <img class="avatar" width="30" height="30" alt="房间" src="static/images/nango.jpg">
                    <p class="name">nango's room</p>
                </li>
                <!-- <li>
                    <img class="avatar" width="30" height="30" alt="webpack" src="../static/images/3.jpg">
                    <p class="name">other</p>
                </li> -->
            </ul>
        </div>
    </div>
    <div class="main">
        <div class="m-message">
            <ul>
                <li>
                    <p class="time"><span></span>
                    </p>
                    <div class="main">
                        <img class="avatar" width="30" height="30" src="static/images/nango.jpg">
                        <div class="nick">nango</div>
                        <div class="text">Hello，这是一个基于Workerman的聊天室，实现实时显示，支持私聊，非轮询。----请文明</div>
                    </div>
                </li>
            </ul>
        </div>
        <!--send-->
        <div class="users">
			在线用户
			<select name="client" class="client">
				
			</select>
		</div>
        <div class="m-text">
            <textarea placeholder="按 Ctrl + Enter 发送" class="input"></textarea>
        </div>
    </div>
</div>
</body>
<script type="text/javascript">
	//发送消息
	$(".input").keypress(function(e) {
		//firefox enter code=13 ; chrome = 10		
		if (e.ctrlKey && (e.which == 13 || e.which == 10)){
			var text = $(".input").val();
			var to_client_id = $(".client option:selected").val();
			if(text == ''){
				alert('不能发送空内容！');
				return;
			}

			var type = to_client_id == 'all'?"say":"prisay";	
			//var content = '{"type":"'+ type +'","content":"'+ text +'","name":"'+ name +'","to_client_id":"'+ to_client_id +'"}';
			//发送者client_id
			var client_id = sessionStorage.client_id;
			var content = {
				'type' : type,
				'content' : text,
				'name' : name,
				'to_client_id' : to_client_id,
				'client_id' : client_id
			};
			
			//简单整合 TP + workerman，用户输入内容-》TP-》workerman-》浏览器显示
			$.ajax({
				url : "",
				datatype : 'text',
				method : 'post',
				data : content,
				success : function(data){
					console.log(data);
				},
				error : function(data){
					console.log(data);
				}
			});
			//ws.send(content);
			$(".input").val('').focus();
		}
	});
	
	$(document).ready(function(){
		if(navigator.userAgent.match(/(iPhone|iPod|Android|ios)/i)){
			alert('暂不支持移动端访问，请移步PC端！');
			return false;
		}
		//初始化
		$.ajax({
				url : "index.php?act=init",
				datatype : 'json',
				method : 'post',
				data : {},
				success : function(data){
					var res = JSON.parse(data);
					var userlist = res.ContactList;	
					var str = '';
					for (var key in userlist) {
						var img = 'http://wx.qq.com' + userlist[key].HeadImgUrl;
						str += '<li class="active">'
                    		+'<img class="avatar" width="30" height="30"  src="'+ img +'" />'
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
	});
	</script>
</html>