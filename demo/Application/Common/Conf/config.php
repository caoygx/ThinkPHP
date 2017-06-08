<?php
//$custom = include ONLINE ? 'pro.php' : 'dev.php';
$custom = include CONF_ENV . ".php";
$http_host = $_SERVER['HTTP_HOST'];
if(filter_var($http_host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false){
    $domain = $http_host;
}else{
    $arr = explode('.',$http_host);
    $c = count($arr);
    $domain = $arr[$c-2].'.'.$arr[$c-1];
}
define('DOMAIN', $domain);

//公共配置
$conf = array(

    //redis配置
    "REDIS_AUTH_PASSWORD" => "Riex7M4esJ",

    //禁止访问的黑名单配置
    'blacklist' => [
        'controller' => [
            // 'message'
        ],
        'action' => [
            //'delete'
        ],
        'url' => [
            'user/lists'
        ],


    ],
    'fake_item' => '0',
    'FREE_DAYS' => '3', //免费天数,给客户端提示用，要改免费天数，去project表里改。
    'MAX_BIND_DEVICE' => 2,
    'REDIS_HOST' => "redis-server",
    'TAGLIB_PRE_LOAD' => 'html',
    'options' => array(

        "vpn_user_status" => array(
            0 => '禁用',
            1 => '正常',
            2 => '待审核',
        )
    ),

    'crypt_key' => "1a2b3c4d5e",

    'DEFAULT_FILTER' => '', //过滤函数

    //'URL_CASE_INSENSITIVE' => true,

    'URL_MODEL' => 3, //默认1;URL模式：0 普通模式 1 PATHINFO 2 REWRITE 3 兼容模式
    'ROUTER_ON' => true, // 是否开启URL路由
    'DEFAULT_MODULE' => 'Home',
    'APP_SUB_DOMAIN_DEPLOY' => 1, // 开启子域名配置
    'APP_SUB_DOMAIN_RULES' => array(
        'godm.com' => 'Home',
    ),

    'LAYOUT_ON' => false,
    'LAYOUT_NAME' => 'layout',


    'TMPL_PARSE_STRING' => array(
        '__PUBLIC__' => URL_PUBLIC, // 更改默认__PUBLIC__
        '__SKIN__' => URL_SKIN, //
        '__UPLOAD__' => '/Uploads', //
    ),

    //上传配置
    'SAVE_PATH' => "uploads/",
    'SHARE_CR_CODE' => 'ssvpnhh',//邀请码用户id加密salt


);


return array_merge($conf, $custom);

