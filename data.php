<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/16
 * Time: 14:11
 */
require_once(__DIR__.'/include/common.php');
$myindex = empty($_REQUEST['myindex'])? 'mumayi':$_REQUEST['myindex'];  // 索引
$mytype = empty($_REQUEST['mytype'])? 'app':$_REQUEST['mytype'];
$myid = $_REQUEST['myid'];
$type = $_REQUEST['type'];  // 1单条数据插入；2批量数据插入；3更新数据；4删除数据
$p = empty($_REQUEST['p'])? 1:$_REQUEST['p'];
$pagenum = empty($_REQUEST['pagenum'])? 10000:$_REQUEST['pagenum'];
$fields = 'appid,type,title,packagename,status,recommend,cid,created,frontdownload,score,adminstatus,icon,introduction,softsize,versioncode';


switch ($type){
    case 1:
        $rtn = index($client, $myindex, $mytype, $fields);
        break;
    case 2:
        $rtn = bulk($client, $myindex, $mytype, $fields);
        break;
    case 3:
        $rtn = update($client, $myindex, $mytype, $fields);
        break;
    case 4:
        $rtn = delete($client, $myindex, $mytype, $myid);
        break;
}

echo json_encode($rtn);
exit();

// 单条数据录入
function index($client, $myindex, $mytype, $fields){
    // &appid=1&title=木蚂蚁市场&packagename=com.mumayi.market.ui&status=2&recommend=提供海量最新应用游戏免费下载&cid=47&created=1526050836&frontdownload=46952230&score=5&adminstatus=0&icon=/uploads/android/img_mumayi/2016/12/29/0/1/icon/1_e771e.png&introduction=超值市场&softsize=8254208&versioncode=325
    $fields_arr = explode(',', $fields);
    foreach ($fields_arr as $k){
        $postData[$k] = $_REQUEST[$k];
    }
    $params = array(
        'index' => $myindex,
        'type' => $mytype,
        'id' => $postData['appid'],
        'body' => $postData
    );
    // var_dump($params);die;
    $rtn = $client->index($params);
    return $rtn;
}
// 批量数据插入
function bulk($client, $myindex, $mytype, $fields){
//    $sql = 'select '.$fields.' from mumayi_soft WHERE status>0 AND sid=0 AND adminstatus>-1 ORDER BY id ASC limit 1,10';
//    $sql = 'select '.$fields.' from mumayi_soft WHERE id IN (995595,998638,917642,1228108,1061932,1197193,640934)';
//    $appdata = $DEVDB->query($sql);
//    while ($row = mysql_fetch_assoc($appdata)){
//        $params['body'][$i] = array(
//            'index' => array(
//                '_index' => $myindex,
//                '_type' => $mytype,
//                '_id'  => $row['id']
//            )
//        );
//        $fields_arr = explode(',', $fields);
//        foreach ($fields_arr as $k){
//            $params['body'][$i][$k] = $row[$k];
//        }
//        print_r($params);die;
//    }

    $appdata = mumayi_app();
    $appdata = json_decode($appdata, true);
    foreach ($appdata as $k => $v){
        $params['body'][$k] = array(
            'index' => array(
                '_index' => $myindex,
                '_type' => $mytype,
                '_id'  => $v['appid']
            )
        );
        $fields_arr = explode(',', $fields);
        foreach ($fields_arr as $a){
            $params['body'][$k][$a] = $v[$a];
        }
    }
//    print_r($params);die;
    $rtn = $client->bulk($params);
    return $rtn;
}
// 数据更新
function update($client, $myindex, $mytype, $fields){

    $fields_arr = explode(',', $fields);
    foreach ($fields_arr as $k){
        if(isset($_REQUEST[$k])){
            $postData[$k] = $_REQUEST[$k];
        }
    }
    $params = array(
        'index' => $myindex,
        'type' => $mytype,
        'id' => $postData['appid'],
        'body' => array(    // 必须带上这个.表示是文档操作
            'doc' => $postData
        )
    );
//    print_r($params);die;
    $rtn = $client->update($params);
    return $rtn;
}
// 数据删除
function delete($client, $myindex, $mytype, $myid){
    $params = array(
        'index' => $myindex,
        'type' => $mytype,
        'id' => $myid
    );
//    print_r($params);die;
    $rtn = $client->delete($params);
    return $rtn;
}

function mumayi_app($p, $pagenum){
    $url = 'http://dev.mumayi.com/es/esdata.php?p='.$p.'&pagenum='.$pagenum;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $appdata = curl_exec($curl);
    curl_close($curl);
    return $appdata;
}