<?php
require_once "send.php";
$token = "你的token";
//token
$post = file_get_contents("php://input");
$post = '[' . $post . ']';
$data = json_decode($post,true);
//获取用户发送的消息
$message = $data[0]['message']['text'];
$type = $data[0]['message']['entities'][0]['type'];
$chatid = $data[0]['message']['chat']['id'];
$messageid = $data[0]['message']['message_id'];
//分析用户发的消息
//$myfile = fopen("newfilssse.txt", "w");
//fwrite($myfile, $post);
//fclose($myfile);


$data = explode(" ",$message);
$message = str_replace("@xneteasebot","",$data[0]);
$content = $data[1];
$content2 = $data[2];
//分隔消息
if ($type == "bot_command") {
  switch ($message) {
    case '/ping':
      $data = array(
        "chat_id" => $chatid,
        "text" => 'pong',
      );
      send($data);
      break;

    case '/search':
      if (empty($content)) {
        $data = array(
          "chat_id" => $chatid,
          "text" => '使用方式:/search 关键词 (个数)',
          "reply_to_message_id" => $messageid
        );
        send($data);
        exit();
      }

      $data = array(
        "chat_id" => $chatid,
        "text" => 'searching...',
      );
      send($data);


      $url = "https://api.xsot.cn/netease/?type=search&limit=$content2&s=$content";
      @$data = file_get_contents($url);
      @$data = json_decode($data,true);
      @$num1 = count($data['result']['songs']);
      if ($num1 == 0) {
        $data = array(
          "chat_id" => $chatid,
          "text" => '没搜到唉,换个关键词试试呗',
          "reply_to_message_id" => $messageid
        );
        send($data);
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
      $textsend = "<pre>";
      for ($i = 0;$i <= $num -1;$i++) {
        $textsend .= $i+1 . " (id:" . $re[$i][0] . ") -> " .  $re[$i][1] . " - " . $re[$i][2] . "</pre><pre>";
      }
      $textsend .= "</pre>";
      $data = array(
        "chat_id" => $chatid,
        "text" => $textsend,
        "parse_mode" => "HTML",
        "disable_web_page_preview" => true,
        "reply_to_message_id" => $messageid
      );
      send($data);
      break;
    case '/song2':
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
        "text" => 'Loading...',
      );
      send($data);
      if ($type == "id") {
        $url = "https://api.xsot.cn/netease/?type=song&id=" . $content;
        @$data = file_get_contents($url);
        @$data = json_decode($data,true);

        if ($data['code'] == 404) {
          $data = array(
            "chat_id" => $chatid,
            "text" => 'id好像输错了唉',
            "reply_to_message_id" => $messageid
          );
          send($data);
        }
        //id检测
        @$url = $data['data']['url'];
        @$name = $data['data']['name'];
        @$artist = $data['data']['artist'];
        $data = array(
          "chat_id" => $chatid,
          "audio" => $url,
          "caption" => "$name - $artist",
          "title" => "$name - $artist",
          "performer" => $artist,
          "reply_to_message_id" => $messageid
        );
        sendaudio($data);
      } else {
        $url = "https://api.xsot.cn/netease/?type=search&limit=1&s=$content";
        @$data = file_get_contents($url);
        @$data = json_decode($data,true);
        @$num1 = count($data['result']['songs']);
        if ($num1 == 0) {
          $data = array(
            "chat_id" => $chatid,
            "text" => '没搜到唉,换个关键词试试呗',
            "reply_to_message_id" => $messageid
          );
          send($data);
          exit();
        }
        $id = $data['result']['songs'][0]['id'];
        $url = "https://api.xsot.cn/netease/?type=song&id=$id";
        @$data = file_get_contents($url);
        @$data = json_decode($data,true);
        @$url = $data['data']['url'];
        @$name = $data['data']['name'];
        @$artist = $data['data']['artist'];
        $data = array(
          "chat_id" => $chatid,
          "audio" => $url,
          "caption" => "$name - $artist",
          "title" => "$name - $artist",
          "performer" => $artist,
          "reply_to_message_id" => $messageid
        );
        sendaudio($data);
      }
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
        "text" => 'Loading...',
      );
      send($data);
      if ($type == "id") {
        $url = "https://api.xsot.cn/netease/?type=song&id=$content";
        @$data = file_get_contents($url);
        @$data = json_decode($data,true);

        if ($data['code'] == 404) {
          $data = array(
            "chat_id" => $chatid,
            "text" => 'id好像输错了唉',
            "reply_to_message_id" => $messageid
          );
          send($data);
        }
        //id检测
        @$url = $data['data']['url'];
        @$name = $data['data']['name'];
        @$artist = $data['data']['artist'];
        $data = array(
          "chat_id" => $chatid,
          "text" => $url,
          "reply_to_message_id" => $messageid
        );
        send($data);
      } else {
        $url = "https://api.xsot.cn/netease/?type=search&limit=1&s=$content";
        @$data = file_get_contents($url);
        @$data = json_decode($data,true);
        @$num1 = count($data['result']['songs']);
        if ($num1 == 0) {
          $data = array(
            "chat_id" => $chatid,
            "text" => '没搜到唉,换个关键词试试呗',
            "reply_to_message_id" => $messageid
          );
          send($data);
          exit();
        }
        $id = $data['result']['songs'][0]['id'];
        $url = "https://api.xsot.cn/netease/?type=song&id=$id";
        @$data = file_get_contents($url);
        @$data = json_decode($data,true);
        @$url = $data['data']['url'];
        @$name = $data['data']['name'];
        @$artist = $data['data']['artist'];
        $data = array(
          "chat_id" => $chatid,
          "text" => $url,
          "caption" => "$name - $artist",
          "title" => "$name - $artist",
          "performer" => $artist,
          "reply_to_message_id" => $messageid
        );
        send($data);
      }
      break;
    case '/help':
        $year = date("Y");
      $text = "
xneteasebot v1.0
BY XCSOFT

指令列表:
/search  搜索歌曲
/song  获取直链
/song2  获取音频
/help  帮助

Copyright © 2019-$year XCSOFT. All Rights Reserved.
            ";
      $data = array(
        "chat_id" => $chatid,
        "text" => $text,
        "reply_to_message_id" => $messageid
      );
      send($data);
      break;
    default:
      $data = array(
        "chat_id" => $chatid,
        "text" => "不懂不懂",
        "reply_to_message_id" => $messageid
      );
      send($data);
      break;
  }
} else {
  file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chatid&text=不懂不懂");
}
