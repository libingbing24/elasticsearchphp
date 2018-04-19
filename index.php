<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/16
 * Time: 11:53
 */
require_once(__DIR__.'/include/common.php');

$myindex = $_REQUEST['myindex'];
$mytype = empty($_REQUEST['mytype'])? 'app':$_REQUEST['mytype'];
$type = $_REQUEST['type'];

if(empty($myindex) || empty($type)){
    $rtn['status'] = 0;
    $rtn['data'] = '参数错误';
    echo json_encode($rtn);
}

switch ($type){
    case 1:
        $rtn = add($client,$myindex,$mytype);
        break;
    case 2;
        $rtn = info($client, $myindex);
        break;
    case 3:
        $rtn = mapping($client, $myindex);
        break;
    case 4:
        $rtn = edit_mapping($client, $myindex, $mytype);
        break;
    case 5:
        $rtn = delete($client, $myindex);
        break;
}
print_r($rtn);
exit();

// 创建索引
function add($client, $myindex, $mytype){
    $params = array(
        'index' => $myindex, //索引名（相当于mysql的数据库）
//        'settings' => array(								// 索引配置
//            'number_of_shards' => 5,					// 分片数
//			'number_of_replicas' => 1,                  // 备份数
//            'analysis' => array(
//                'analyzer' => array(
//                    'ik' => array(
//                        'type' => 'IK',
//                        'tokenizer' => 'ik_smart',
//                    )
//                )
//            )
//        ),
        'body' => array(
            'mappings' => array(
                $mytype => array( //类型名（相当于mysql的表）
                    '_all' => array( //是否开启所有字段的检索
                        'enabled' => 'false'
                    ),
                    'properties' => array(  //文档类型设置（相当于mysql的数据类型）
                        'appid' => array(
                            'type' => 'integer' // 字段类型为整型
                        ),
                        'type' => array(
                            'type' => 'integer'
                        ),
                        'title' => array(
                            'type' => 'text',    // 字段类型为关键字,如果需要全文检索,则修改为text,注意keyword字段为整体查询,不能作为模糊搜索
                            'index' => 'analyzed', //(默认analyzed首先分析字符串，然后索引它。换句话说，以全文索引这个域；not_analyzed索引这个域，所以它能够被搜索，但索引的是精确值。不会对它进行分析；no不索引这个域。这个域不会被搜索到。)
                            'analyzer' => 'ik_smart',// 指定在搜索和索引时使用的分析器(ik_max_word ：会将文本做最细粒度的拆分；尽可能多的拆分出词语;ik_smart：会做最粗粒度的拆分；已被分出的词语将不会再次被其它词语占有)
                        ),
                        'packagename' => array(
                            'type' => 'keyword'
                        ),
                        'status' => array(
                            'type' => 'integer'
                        ),
                        'recommend' => array(
                            'type' => 'text'
                        ),
                        'cid' => array(
                            'type' => 'integer'
                        ),
                        'created' => array(
                            'type' => 'date',
                            "format" => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis'
                        ),
                        'frontdownload' => array(
                            'type' => 'integer'
                        ),
                        'score' => array(
                            'type' => 'integer'
                        ),
                        'adminstatus' => array(
                            'type' => 'integer'
                        )
                    )
                )
            )
        )
    );
    $rtn = $client->indices()->create($params);
    return $rtn;
}
// 获取索引信息
function info($client, $myindex){
    $params = array(
        'index' => $myindex,
        'client' => array(
            'ignore' => 404
        )
    );
    $rtn = $client->indices()->getSettings($params);    //获取库索引设置
    return $rtn;
}
// 获取Mapping信息
function mapping($client, $myindex){
    $params = array(
        'index' => $myindex,
        'client' => array(
            'ignore' => 404
        )
    );
    $rtn = $client->indices()->getMapping($params);   //获取mapping信息
    return $rtn;
}
// 修改/添加mapping信息 (已经建立好的字段类型是不能更改的)
function edit_mapping($client, $myindex, $mytype){
    $field = $_REQUEST['field'];    // 字段名
    $ftype = $_REQUEST['ftype'];    // 字段类型（integer，text，keyword，date）
    $ftype_arr = array('integer','text','keyword','date');
    $params = array(
        'index' => $myindex,  //索引名（相当于mysql的数据库）
        'type'  => $mytype,
        'body'  => array(
            $mytype => array(
                'properties' => array(
                    $field => array(
                        'type'  => $ftype_arr[$ftype]
                    )
                )
            )
        )
    );
//    print_r($params);die;
    $rtn = $client->indices()->putMapping($params);
    return $rtn;
}
// 删除索引
function delete ($client, $myindex){
    $params = array(
        'index' => $myindex,  //索引名（相当于mysql的数据库）
    );
    $rtn = $client->indices()->delete($params);
    return $rtn;
}

