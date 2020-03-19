<?php
// doHttpPost ：执行POST请求，并取回响应结果
// 参数说明
//   - $url   ：接口请求地址
//   - $params：完整接口请求参数（特别注意：不同的接口，参数对一般不一样，请以具体接口要求为准）
// 返回数据
//   - 返回false表示失败，否则表示API成功返回的HTTP BODY部分
require_once "getreqsign.php";
function doHttpPost($url, $params) {
    $curl = curl_init();

    $response = false;
    do
    {
        // 1. 设置HTTP URL (API地址)
        curl_setopt($curl, CURLOPT_URL, $url);

        // 2. 设置HTTP HEADER (表单POST)
        $head = array(
            'Content-Type: application/x-www-form-urlencoded'
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $head);

        // 3. 设置HTTP BODY (URL键值对)
        $body = http_build_query($params);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

        // 4. 调用API，获取响应结果
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_NOBODY, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        if ($response === false) {
            $response = false;
            break;
        }

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($code != 200) {
            $response = false;
            break;
        }
    }
    while (0);

    curl_close($curl);
    return $response;
}

// 设置请求数据（应用密钥、接口请求参数）
function chat($question,$session) {
    $appkey = 'APPKEY';
    $time = time();
    $params = array(
        'app_id' => 'APP_ID',
        'time_stamp' => $time,
        'nonce_str' => 'XXXXXX',
        'question' => $question,
        'session' => $session
    );
    $params['sign'] = getReqSign($params, $appkey);

    // 执行API调用
    $url = 'https://api.ai.qq.com/fcgi-bin/nlp/nlp_textchat';
    $response = doHttpPost($url, $params);
    $data = json_decode($response,true);
    $code = $data['ret'];
    $message = $data['data']['answer'];
    return array(
        "code" => $code,
        "message" => $message
        );
}
