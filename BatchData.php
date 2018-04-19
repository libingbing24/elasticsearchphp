<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/19
 * Time: 16:00
 */

$myindex = 'myik';
$mytype = 'app';
$p = 6;
$pagenum = 10000;

$appdata = mumayi_app($p, $pagenum);
$appdata = json_decode($appdata, true);
foreach ($appdata as $k => $v){
    $params = array(
        'index' => $myindex,
        'type' => $mytype,
        'id' => $v['appid'],
        'body' => $v
    );
//            print_r($params);die;
    $rtn = $client->index($params);
    print_r($rtn);
}
die;

function mumayi_app($p, $pagenum){
    $url = 'http://dev.mumayi.com/es/esdata.php?p='.$p.'&pagenum='.$pagenum;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $appdata = curl_exec($curl);
    curl_close($curl);
    return $appdata;
}