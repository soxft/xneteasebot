<?php
require_once "send.php";
require_once "download.php";
require_once "chat.php";
require_once "time.php";
require_once "space.php";
require_once "server.php";
$conn = mysqli_connect("localhost","telegram","telegram","telegram");
$token = "";
//token
$post = file_get_contents("php://input");
$post = '[' . $post . ']';
$data = json_decode($post,true);
//获取用户发送的消息
$message = $data[0]['message']['text'];
$type = $data[0]['message']['entities'][0]['type'];
$chatid = $data[0]['message']['chat']['id'];
$messageid = $data[0]['message']['message_id'];
$userid = $data[0]['message']['from']['id'];
$chattype = $data[0]['message']['chat']['type'];
$username = $data[0]['message']['from']['username'];
//supergroup / private
//分析用户发的消息

$myfile = fopen("m.txt", "a");
fwrite($myfile, $post);
fclose($myfile);


if (!empty($userid)) {
    $sql = "select * from `user` where `userid` = '$userid'";
    $check = mysqli_query($conn,$sql);
    $arr = mysqli_fetch_assoc($check);
    if (empty($arr)) {
        //没有帐户，则创建账户
        mysqli_query($conn,"INSERT INTO `user` VALUES('$userid','0','','')");
    }
}
$message = spaces($message);
//防止过多空格
$data = explode(" ",$message);
$mess = str_replace("@xneteasebot","",$data[0]);
//去除@后的句子
$content = $data[1];
$content2 = $data[2];
//分隔消息

//指令判断
if ($type == "bot_command") {
    switch ($mess) {
        case '/start':
            $data = array(
                "chat_id" => $chatid,
                "text" => "欢迎使用xneteasebot - 一款网易云音乐的解析bot\npowered by xcsoft\n\n联系方式:@xcontact_bot\n博客: https://blog.xsot.cn",
                "disable_web_page_preview" => true,
                "reply_to_message_id" => $messageid
            );
            send($data);
            break;
            break;
        case '/ping':
            //在线检测
            $data = array(
                "chat_id" => $chatid,
                "text" => 'pong',
            );
            send($data);
            break;
        case '/search':
            //歌曲搜索
            if (empty($content) && empty($content2)) {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => '使用方式:/search 关键词',
                    "reply_to_message_id" => $messageid
                );
                send($data);
                exit();
                //是否输入完全
            }

            $data = array(
                "chat_id" => $chatid,
                "text" => '搜索中...',
                "reply_to_message_id" => $messageid
            );
            $return = send($data);
            $sendmessageid = $return['result']['message_id'];

            //输出搜索中
            $content = str_replace("/search ","",$message);
            $url = "https://api.xsot.cn/netease/?type=search&s=$content";
            @$data = file_get_contents($url);
            @$data = json_decode($data,true);
            @$num1 = count($data['result']['songs']);
            if ($num1 == 0) {
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => '没搜到唉,换个关键词试试呗'
                );
                edit($data);
                exit();
            }
            $result = array();
            for ($i = 0;$i <= $num1 - 1;$i++) {
                $result[$i] = $data['result']['songs'][$i];
            }
            $num = count($result);
            $re = array();
            //数组初始化
            for ($i = 0;$i <= $num - 1;$i++) {
                $re[$i] = array(
                    $result[$i]['id'],
                    $result[$i]['name'],
                    $result[$i]['artists'][0]['name']
                );
            }
            for ($i = 0;$i <= $num -1;$i++) {
                $textsend .= $i+1 . " (id:" . $re[$i][0] . ")  ->  " .  $re[$i][1] . " - " . $re[$i][2] . "\n";
            }
            $data = array(
                "chat_id" => $chatid,
                "message_id" => $sendmessageid,
                "text" => $textsend,
                "disable_web_page_preview" => true
            );
            edit($data);
            break;
        case '/song':
            $type = $content;
            $content = $data[2];
            if (empty($content) || empty($type) || ($type !== "name" && $type !== "id")) {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => '使用方式:/song [id/name] 关键词',
                    "reply_to_message_id" => $messageid
                );
                send($data);
                exit();
            }
            $data = array(
                "chat_id" => $chatid,
                "text" => '搜索中...',
                "reply_to_message_id" => $messageid
            );
            $return = send($data);
            $sendmessageid = $return['result']['message_id'];

            //返回加载中
            if ($type == "id") {
                //id搜索类型
                $arr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM `music` WHERE id='$content'"));
                //搜索数据库是都存在fileid
                $name = $arr['name'];
                if (empty($arr)) {
                    $unexist = true;
                } else {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => "「 $name 」\n命中缓存,发送中..."
                    );
                    edit($data);
                    $musicurl = $arr['fileid'];
                    $artist = $arr['artist'];
                    $name = $arr['name'];
                    $pic = $arr['pic'];
                    goto jump;
                }
                //判断是否存在
                $url = "https://api.xsot.cn/netease/?type=song&id=" . $content;
                @$data = file_get_contents($url);
                @$data = json_decode($data,true);
                if ($data['code'] == 404) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => 'id好像输错了唉'
                    );
                    edit($data);
                    exit();
                }
                //id检测
                $url = $data['data']['url'];
                $name = $data['data']['name'];
                $artist = $data['data']['artist'];
                $pic = $data['data']['pic'];
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => "「 $name 」\n缓存中..."
                );
                edit($data);
                $musicurl = download($url,"$name - $artist");
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => "「 $name 」\n已缓存,发送中..."
                );
                edit($data);
                jump:
                $reply = array(
                    "inline_keyboard" => array(
                        array(
                            array(
                                'text' => "$name - $artist",
                                'url' => "https://music.163.com/#/song?id=$content"
                            )
                        )
                    )
                );
                $data = array(
                    "chat_id" => $chatid,
                    "audio" => $musicurl,
                    "title" => "$name - $artist",
                    "performer" => $artist,
                    "thumb" => $pic,
                    "reply_markup" => $reply,
                    "reply_to_message_id" => $messageid
                );
                $data = sendaudio($data);
                $delarr = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid
                );
                del($delarr);
                if ($unexist) {
                    $fileid = $data['result']['audio']['file_id'];
                    if (!empty($fileid)&&!empty($content)) {
                        mysqli_query($conn,"INSERT INTO `music` VALUES('$content','$fileid','$name','$artist','$pic')");
                    }
                }
            } else {
                //name搜索类型
                $content = str_replace("/song name","",$message);
                $url = "https://api.xsot.cn/netease/?type=search&limit=1&s=$content";
                $data = file_get_contents($url);
                $data = json_decode($data,true);
                $num1 = count($data['result']['songs']);
                if ($num1 == 0) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => '没搜到唉,换个关键词试试呗'
                    );
                    edit($data);
                    exit();
                }
                $id = $data['result']['songs'][0]['id'];
                $arr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM `music` WHERE id='$id'"));
                $name = $arr['name'];
                //搜索数据库是都存在fileid
                if (empty($arr)) {
                    $unexist = true;
                } else {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => "「 $name 」\n命中缓存,发送中..."
                    );
                    edit($data);
                    $musicurl = $arr['fileid'];
                    $artist = $arr['artist'];
                    $name = $arr['name'];
                    $pic = $arr['pic'];
                    goto jump2;
                }
                //判断是否存在
                $url = "https://api.xsot.cn/netease/?type=song&id=$id";
                @$data = file_get_contents($url);
                @$data = json_decode($data,true);
                if ($data['code'] == 404) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => '该歌曲失效啦,换个关键词试试'
                    );
                    edit($data);
                    exit();
                }
                $url = $data['data']['url'];
                $name = $data['data']['name'];
                $artist = $data['data']['artist'];
                $pic = $data['data']['pic'];
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => "「 $name 」\n缓存中..."
                );
                edit($data);
                $musicurl = download($url,"$name - $artist");
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => "「 $name 」\n已缓存,发送中..."
                );
                edit($data);
                jump2:
                $reply = array(
                    "inline_keyboard" => array(
                        array(
                            array(
                                'text' => "$name - $artist",
                                'url' => "https://music.163.com/#/song?id=$id"
                            )
                        )
                    )
                );
                $data = array(
                    "chat_id" => $chatid,
                    "audio" => $musicurl,
                    "title" => "$name - $artist",
                    "performer" => $artist,
                    "thumb" => $pic,
                    "reply_markup" => $reply,
                    "reply_to_message_id" => $messageid
                );
                $data = sendaudio($data);
                $delarr = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid
                );
                del($delarr);

                if ($unexist) {
                    $fileid = $data['result']['audio']['file_id'];
                    if (!empty($fileid) && !empty($id)) {
                        mysqli_query($conn,"INSERT INTO `music` VALUES('$id','$fileid','$name','$artist','$pic')");
                    }
                }
            }
            break;
        case '/songlink':
            $type = $content;
            $content = $data[2];
            if (empty($content) || empty($type) || ($type !== "name" && $type !== "id")) {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => '使用方式:/songlink [id/name] 关键词',
                    "reply_to_message_id" => $messageid
                );
                send($data);
                exit();
            }
            $data = array(
                "chat_id" => $chatid,
                "text" => '搜索中...',
                "reply_to_message_id" => $messageid
            );
            $return = send($data);
            $sendmessageid = $return['result']['message_id'];
            //加载中,并获取id
            if ($type == "id") {
                $url = "https://api.xsot.cn/netease/?type=song&id=$content";
                @$data = file_get_contents($url);
                @$data = json_decode($data,true);

                if ($data['code'] == 404) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => 'id好像输错了唉'
                    );
                    edit($data);
                    exit();
                }
                //id检测
                @$url = $data['data']['url'];
                @$name = $data['data']['name'];
                @$artist = $data['data']['artist'];
                $reply = array(
                    "inline_keyboard" => array(
                        array(
                            array(
                                'text' => "$name - $artist",
                                'url' => "$url"
                            )
                        )
                    )
                );
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => "$url",
                    "reply_markup" => $reply,
                );
                edit($data);
            } else {
                $content = str_replace("/songlink name","",$message);
                $url = "https://api.xsot.cn/netease/?type=search&limit=1&s=$content";
                @$data = file_get_contents($url);
                @$data = json_decode($data,true);
                @$num1 = count($data['result']['songs']);
                if ($num1 == 0) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => '没搜到唉,换个关键词试试呗'
                    );
                    edit($data);
                    exit();
                }
                $id = $data['result']['songs'][0]['id'];
                $url = "https://api.xsot.cn/netease/?type=song&id=$id";
                @$data = file_get_contents($url);
                @$data = json_decode($data,true);
                if ($data['code'] == 404) {
                    $data = array(
                        "chat_id" => $chatid,
                        "text" => '该歌曲失效啦,换个关键词试试',
                        "message_id" => $sendmessageid,
                        "reply_to_message_id" => $messageid
                    );
                    edit($data);
                    exit();
                }
                @$url = $data['data']['url'];
                @$name = $data['data']['name'];
                @$artist = $data['data']['artist'];
                $reply = array(
                    "inline_keyboard" => array(
                        array(
                            array(
                                'text' => "$name - $artist",
                                'url' => "$url"
                            )
                        )
                    )
                );
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => "$url",
                    "reply_markup" => $reply,
                );
                edit($data);
            }
            break;
        case '/lyric':
            $type = $content;
            $content = $data[2];
            if (empty($content) || empty($type) || ($type !== "name" && $type !== "id")) {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => '使用方式:/lyric [id/name] 关键词',
                    "reply_to_message_id" => $messageid
                );
                send($data);
                exit();
            }
            $data = array(
                "chat_id" => $chatid,
                "text" => '搜索中...',
                "reply_to_message_id" => $messageid
            );
            $return = send($data);
            $sendmessageid = $return['result']['message_id'];
            //加载中,并获取id
            if ($type == "id") {
                $url = "http://music.163.com/api/song/media?id=$id";
                @$data = file_get_contents($url);
                @$data = json_decode($data,true);


                if (empty($data['lyric'])) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => 'id好像输错了唉'
                    );
                    edit($data);
                    exit();
                }
                //id检测
                $lyric = $data['lyric'];
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => $lyric
                );
                edit($data);
            } else {
                $content = str_replace("/lyric name","",$message);
                $url = "https://api.xsot.cn/netease/?type=search&limit=1&s=$content";
                @$data = file_get_contents($url);
                @$data = json_decode($data,true);
                @$num1 = count($data['result']['songs']);
                if ($num1 == 0) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => '没搜到唉,换个关键词试试呗'
                    );
                    edit($data);
                    exit();
                }
                $id = $data['result']['songs'][0]['id'];
                $url = "http://music.163.com/api/song/media?id=$id";
                @$data = file_get_contents($url);
                @$data = json_decode($data,true);

                if (empty($data['lyric'])) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => '该歌曲失效啦,换个关键词试试'
                    );
                    edit($data);
                    exit();
                }
                //id检测
                $lyric = $data['lyric'];
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => $lyric
                );
                edit($data);
            }
            break;
        case '/signin':
            $sql = "select * from `user` where `userid` = '$userid'";
            $check = mysqli_query($conn,$sql);
            $arr = mysqli_fetch_assoc($check);
            @$integral = $arr['integral'];
            $date = date("Y-m-d");
            $sql = "SELECT * FROM `signin` WHERE `date`= '$date'";
            $check = mysqli_query($conn,$sql);
            $arr = mysqli_fetch_assoc($check);
            if (empty($arr)) {
                //检测数据库是否存在今天的记录,如果没有则创建
                mysqli_query($conn,"INSERT INTO `signin` VALUES('$date','')");
            }
            $signind = $arr['userid'];
            $signindarr = explode(",",$signind);
            //已签到记录，并转换成数组
            $num = count($signindarr);
            //已签到人数
            if (in_array($userid,$signindarr)) {
                //如果已经签到则输出
                $data = array(
                    "chat_id" => $chatid,
                    "text" => '你今天已经签过到了!',
                    "reply_to_message_id" => $messageid
                );
                send($data);
                exit();
            }
            if ($num < 2) {
                $add = rand(95,100);
            } else if ($num >= 2 && $num < 5) {
                $add = rand(80,95);
            } else if ($num >= 5 && $num < 20) {
                $add = rand(60,80);
            } else if ($num >= 20 && $num < 50) {
                $add = rand(40,60);
            } else {
                $add = rand(20,40);
            }
            //根据名次随机加分
            $integral_all = $integral + $add;
            mysqli_query($conn,"UPDATE `user` SET `integral` = '$integral_all' WHERE `userid` = '$userid'");
            //获取总分数并update数据库
            $userid_all = $signind . $userid . ",";
            mysqli_query($conn,"UPDATE `signin` SET `userid` = '$userid_all' WHERE `date` = '$date'");
            //计算目前总userid并update数据库
            $data = array(
                "chat_id" => $chatid,
                "text" => "签到成功\n你今天第 $num 个签到\n获得积分$add",
                "disable_web_page_preview" => true,
                "reply_to_message_id" => $messageid
            );
            send($data);
            break;
        case '/tianqi':
            if (empty($content) || empty($content2) || ($content !== 'search' && $content !== 'area')) {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => '使用方式:/tianqi [area/search] 地区',
                    "reply_to_message_id" => $messageid
                );
                send($data);
                exit();
            }
            $data = array(
                "chat_id" => $chatid,
                "text" => '获取中...',
                "reply_to_message_id" => $messageid
            );
            $return = send($data);
            $sendmessageid = $return['result']['message_id'];

            if ($content == "area") {
                $url = "https://api.xsot.cn/weather/?area=$content2";
                $data = file_get_contents($url);
                $arr = json_decode($data,true);

                // $myfile = fopen("neweathersse.txt", "w");
                //fwrite($myfile, $arr);
                //fclose($myfile);

                if ($arr['code'] !== 200) {
                    //检测是都存在该地区
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => "未搜索到该地区,试试换个名字吧",
                    );
                    edit($data);
                } else {
                    $area = $arr['area'];
                    $time = $arr['time'];
                    $suggestion = $arr['suggestion'];

                    $nowtemp = $arr['result']['now']['temp'];
                    $nowweather = $arr['result']['now']['weather'];

                    $todaytemplow = $arr['result']['today']['temp'][0];
                    $todaytemphigh = $arr['result']['today']['temp'][1];
                    $todayweather = $arr['result']['today']['weather'];

                    $tomorrowtemplow = $arr['result']['tomorrow']['temp'][0];
                    $tomorrowtemphigh = $arr['result']['tomorrow']['temp'][1];
                    $tomorrowweather = $arr['result']['tomorrow']['weather'];

                    $thirddaytemplow = $arr['result']['thirdday']['temp'][0];
                    $thirddaytemphigh = $arr['result']['thirdday']['temp'][1];
                    $thirddayweather = $arr['result']['thirdday']['weather'];

                    $url = $arr['url'];

                    $reply = array(
                        "inline_keyboard" => array(
                            array(
                                array(
                                    'text' => "详细信息",
                                    'url' => "$url"
                                )
                            )
                        )
                    );
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => "$area\n更新时间: $time\n\n建议: $suggestion\n\n目前气温: $nowtemp\n目前天气: $nowweather\n\n今天\n气温: $todaytemplow - $todaytemphigh\n 天气: $todayweather\n\n明天\n气温: $tomorrowtemplow - $tomorrowtemphigh\n 天气: $tomorrowweather\n\n后天\n气温: $thirddaytemplow - $thirddaytemphigh\n 天气: $thirddayweather",
                        "reply_markup" => $reply,
                        "disable_web_page_preview" => true
                    );
                    edit($data);
                }
            } else {
                $url = "https://api.xsot.cn/weather/?search=$content2";
                $data = file_get_contents($url);
                $data = json_decode($data,true);
                if ($data['code'] !== 200) {
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => "未搜索到该地区,试试换个名字吧",
                        "disable_web_page_preview" => true
                    );
                    edit($data);
                } else {
                    $num = count($data['result']);
                    for ($i = 0;$i < $num;$i++) {
                        $re .= $i + 1 . " -> " . $data['result'][$i]['area'] . "\n";
                    }
                    $data = array(
                        "chat_id" => $chatid,
                        "message_id" => $sendmessageid,
                        "text" => $re,
                        "disable_web_page_preview" => true
                    );
                    edit($data);
                }
            }
            break;
        case '/urlsafe':
            if (empty($content)) {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => '使用方式:/urlsafe url',
                    "reply_to_message_id" => $messageid
                );
                send($data);
                exit();
            }
            $data = array(
                "chat_id" => $chatid,
                "text" => '检测中...',
                "reply_to_message_id" => $messageid
            );
            $return = send($data);
            $sendmessageid = $return['result']['message_id'];
            $content = str_replace("/urlsafe ","",$message);

            $url = "https://api.xsot.cn/urlsafe/?url=$content";
            $data = file_get_contents($url);
            $data = json_decode($data,true);

            if ($data['code'] !== "200") {
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => "网址好像输错了"
                );
                edit($data);
                exit();
            }
            $type = $data['type'];
            $beian = $data['beian'];
            $icpdode = $data['icpdode'];
            //icphao
            $icporg = $data['icporg'];
            //备案主体
            $word = $data['word'];
            //
            $wordtit = $data['wordtit'];
            //
            if ($type == 1) {
                $type = "未知";
            } else if ($type == 2) {
                $type = "危险";
            } else {
                $type = "安全";
            }
            if ($beian == 0) {
                $beian = "否";
            } else {
                $beian = "是";
            }
            $result = "检测网址: $content\n\n安全性: $type\n是否备案: $beian\n\n";
            if ($beian == "是") {
                $result .= "备案号: $icpdode\n备案主体: $icporg\n\n";
            }
            if ($type == "危险") {
                $result .= "报毒标题: $wordtit\n报毒原因: $word";
            }
            $reply = array(
                "inline_keyboard" => array(
                    array(
                        array(
                            'text' => "详细信息",
                            'url' => "https://urlsec.qq.com/check.html?url=$content"
                        )
                    )
                )
            );
            $data = array(
                "chat_id" => $chatid,
                "message_id" => $sendmessageid,
                "text" => $result,
                "reply_markup" => $reply,
                "disable_web_page_preview" => true
            );
            edit($data);
            if ($type)
                break;
        case '/qrc':
            if (empty($content)) {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => '使用方式:/qrc 内容',
                    "reply_to_message_id" => $messageid
                );
                send($data);
                exit();
            }
            $data = array(
                "chat_id" => $chatid,
                "text" => '获取中...',
                "reply_to_message_id" => $messageid
            );
            $return = send($data);
            $sendmessageid = $return['result']['message_id'];
            $content = str_replace("/qrc ","",$message);
            if (mb_strlen($content) > 200) {
                $data = array(
                    "chat_id" => $chatid,
                    "message_id" => $sendmessageid,
                    "text" => '太长了'
                );
                edit($data);
                exit();
            }
            $url = "https://api.xsot.cn/qrcode/?content=$content";
            $reply = array(
                "inline_keyboard" => array(
                    array(
                        array(
                            'text' => "$content",
                            'url' => "https://api.xsot.cn/qrcode/?content=$content"
                        )
                    )
                )
            );
            $data = array(
                "chat_id" => $chatid,
                "photo" => $url,
                "reply_markup" => $reply,
                "reply_to_message_id" => $messageid,
                "disable_web_page_preview" => true
            );
            sendphoto($data);
            $delarr = array(
                "chat_id" => $chatid,
                "message_id" => $sendmessageid
            );
            del($delarr);
            break;
        case '/voice':
            $content = str_replace("/voice ","",$message);
            $return =  "https://tts.baidu.com/text2audio?tex=$content&cuid=baike&lan=ZH&ctp=1&pdt=301&vol=9&rate=32&per=0";
           $data = array(
                    "chat_id" => $chatid,
                    "audio" => $return,
                    "reply_to_message_id" => $messageid
                );
                $data = sendaudio($data);
        break;
        case '/mystats':
            $sql = "select * from `user` where `userid` = '$userid'";
            $check = mysqli_query($conn,$sql);
            $arr = mysqli_fetch_assoc($check);
            @$integral = $arr['integral'];
            $data = array(
                "chat_id" => $chatid,
                "text" => "User: $userid\nIntegral: $integral",
                "disable_web_page_preview" => true,
                "reply_to_message_id" => $messageid
            );
            mysqli_query($conn,"UPDATE `user` SET `night` = '' WHERE `userid`='$userid'");
            send($data);
            break;
        case '/night':
            $arr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM `user` WHERE `userid`='$userid'"));
            $lasttime = $arr['morning'];
            $time = time();
            mysqli_query($conn,"UPDATE `user` SET `night`='$time' WHERE `userid`='$userid'");
            $timec = $time - $lasttime;
            $ftime = ftime($timec);
            if ($ftime == "pass") {
                //设定现在的时间
                $data = array(
                    "chat_id" => $chatid,
                    "text" => "晚安好梦~",
                    "disable_web_page_preview" => true,
                    "reply_to_message_id" => $messageid
                );
            } else {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => "晚安好梦~\n你今天清醒了$ftime",
                    "disable_web_page_preview" => true,
                    "reply_to_message_id" => $messageid
                );
            }
            mysqli_query($conn,"UPDATE `user` SET `morning` = '' WHERE `userid`='$userid'");
            send($data);
            break;
        case '/morning':
            $arr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM `user` WHERE `userid`='$userid'"));
            $lasttime = $arr['night'];
            $time = time();
            mysqli_query($conn,"UPDATE `user` SET `morning`='$time' WHERE `userid`='$userid'");
            $timec = $time - $lasttime;
            $ftime = ftime($timec);
            if ($ftime == "pass") {
                //设定现在的时间
                $data = array(
                    "chat_id" => $chatid,
                    "text" => "早上好~",
                    "disable_web_page_preview" => true,
                    "reply_to_message_id" => $messageid
                );
            } else {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => "早上好~\n你休息了$ftime",
                    "disable_web_page_preview" => true,
                    "reply_to_message_id" => $messageid
                );
            }
            send($data);
            break;
        case '/help':
            $year = date("Y");
            $text = "xneteasebot v1.4\nBY XCSOFT\n\n指令列表:\n/search  搜索歌曲\n/songlink  获取直链\n/song  获取音频\n/lyric  获取歌词\n/signin  签到\n/tianqi  查询天气\n/urlsafe  网址安全检测\n/qrc 二维码生成\n/morning  早安\n/night  晚安\n/mystats  我的状态\n/help  帮助\n\nCopyright © 2019-$year XCSOFT. All Rights Reserved.\n\ncontact:\n@xcontact_bot";
            $data = array(
                "chat_id" => $chatid,
                "text" => $text,
                "disable_web_page_preview" => true,
                "reply_to_message_id" => $messageid
            );
            send($data);
            break;
        default:
            if ($chattype == "private" || strpos($message,'@xneteasebot') !== false) {
                $data = array(
                    "chat_id" => $chatid,
                    "text" => "不懂不懂",
                    "reply_to_message_id" => $messageid
                );
                send($data);
            }
            break;
    }
    //这是switch的后括号

    //指令版本内自定义
} else {
    $mes = str_replace("@xneteasebot","",$message);

    if (strpos($message,'@xneteasebot') !== false || $chattype == "private") {
        //智能聊天
        $data = chat($mes,$userid);
        $chat = $data['message'];
        if ($data['code'] == 0) {
            if ($chattype == 'private') {
                //群组要@
                file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chatid&text=$chat");
            } else {
                file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chatid&reply_to_message_id=$messageid&text=$chat");
            }
        } else {
            file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chatid&text=不懂不懂");
        }
    }
}
