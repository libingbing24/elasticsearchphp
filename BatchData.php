<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/19
 * Time: 16:00
 */
require_once(__DIR__.'/include/common.php');
$myindex = 'mumayi';
$mytype = 'mumayi_soft';
$sum = mumayi_app($p, $pagenum, '1');
$sum = json_decode($sum, true);
$p = 1;
$pagenum = 1000;
$p_end = ceil($sum[0]['sum']/$pagenum);

if($p_end == 1){
    $appdata = mumayi_app($p, $pagenum, '2');
    $appdata = json_decode($appdata, true);
    indata($client, $appdata, $myindex, $mytype);
}else{
    for ($p=1; $p<$p_end; $p++) {
        $n = ($p-1)*$pagenum;
        $appdata = mumayi_app($n, $pagenum, '2');
        $appdata = json_decode($appdata, true);
        // var_dump($n, $pagenum, $appdata);die;

        indata($client, $appdata, $myindex, $mytype);
        // exit('Stop');
    }
}
exit('over');

function mumayi_app($p, $pagenum, $type){
    $fields = ' id as appid,typeid as type,title,packagename,status,recommend,cid,created,frontdownload,score,adminstatus,icon,introduction,softsize,versioncode ';
    $where = ' status>0 AND sid=0 AND adminstatus>-1 AND id>1229604 ';
    $url = 'http://dev.mumayi.com/es/esdata.php?p='.$p.'&pagenum='.$pagenum.'&type='.$type.'&fields='.$fields.'&where='.$where;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $appdata = curl_exec($curl);
    curl_close($curl);
    return $appdata;
}

function indata($client, $appdata, $myindex, $mytype){
    if(count($appdata) > 1){
        foreach ($appdata as $k => $v){
            $params = array(
                'index' => $myindex,
                'type' => $mytype,
                'id' => $v['appid'],
                'body' => $v
            );
            // print_r($params);die;
            $rtn = $client->index($params);
            if (!$rtn) {
                @file_put_contents('/data/www/elasticsearch/log.txt', $v['appid']."\n", FILE_APPEND);
            }else{
                echo '['.$v['appid'].']   is ok' . "\n";
            }
//            print_r($rtn);die;
        }
    }else{
        echo '数据为空';
    }
}