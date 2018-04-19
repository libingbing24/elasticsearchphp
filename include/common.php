<?php
error_reporting(0);
ini_set("display_errors","on");

define('PROJECT_PATH', dirname(dirname(__FILE__)));
define('EGG_SYSTEM', TRUE);
define('REDIS_CACHE_TIME', 3600);
define('BR_', '<br/>');

//require_once(dirname(__FILE__) . "/db/dbdev.class.php");
require_once(PROJECT_PATH . '/vendor/autoload.php');
use Elasticsearch\ClientBuilder;
$hosts = array(
    'host' => 'es-cn-0pp0k3p6l000hwr90.elasticsearch.aliyuncs.com',
    'port' => '9200',
    'scheme' => 'http',
    'user' => 'elastic',
    'pass' => 'MumAYI888',
);
$client = ClientBuilder::create()->setHosts($hosts)->build();

function curlrequest($url,$data,$method='post'){
    $ch = curl_init(); //初始化CURL句柄
    curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
    $document = curl_exec($ch);//执行预定义的CURL
    curl_close($ch);

    return $document;
}