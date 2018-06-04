<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13
 * Time: 14:56
 */
//phpinfo();
require_once(__DIR__.'/include/common.php');
$myindex = empty($_REQUEST['myindex'])? 'mumayi':$_REQUEST['myindex'];
$mytype = empty($_REQUEST['mytype'])? 'mumayi_soft':$_REQUEST['mytype'];
$estype = empty($_REQUEST['estype'])? 1 : $_REQUEST['estype'];    // 1全文搜索；2短语搜索
switch ($estype){
    case 2:
        $st = 'match_phrase';
        break;
    default:
        $st = 'match';
        // $st = 'term';
}
$keyword = str_replace(' ', '', $_REQUEST['keyword']);
$p = empty($_REQUEST['p'])? 1 : $_REQUEST['p'];
$pagenum = empty($_REQUEST['pagenum'])? 20 : $_REQUEST['pagenum'];
$page = ($p-1)*$pagenum;
$fields = array(appid,type,typeid,title,packagename,status,recommend,cid,created,frontdownload,score,adminstatus);  // 查询字段
$analyze = empty($_REQUEST['analyze'])? 2:$_REQUEST['analyze']; // 1 返回详细数据
$isMatch = preg_match('/^(?!_)[A-Za-z0-9_\x{4e00}-\x{9fa5}]+$/u',$keyword);
if(!$isMatch){
    $this->error('非法词，无法搜索！','/',3);
}

// 批量搜索数据
$params = array(
    'index' => $myindex,
    'type' => $mytype,
    'body' => array(
        '_source' => $fields,
        'from' => $page,  // 分页
        'size' => $pagenum,  // 每页数量
        'query' => array(
            $st => array(
                'title' => $keyword,
            )
        ),
       // 'sort' => array( // 排序
            //   'created' => 'desc'
       // )
    )
);

// 组合查询
// $params = array(
//     'index' => $myindex,
//     'type' => $mytype,
//     'body' => array(
//         '_source' => $fields,
//         'from' => $page,  // 分页
//         'size' => $pagenum,  // 每页数量
//         'query' => array(
//             'bool' => array(
//                 'filter' => array(
//                     'range' => array(
//                         'appid' => array(
//                             'gt' => 0
//                         )
//                     )
//                 ),
//                 'must' => array(
//                     $st => array(
//                         'title' => $keyword,
//                     )
//                 )
//             ),
//         ),
//        // 'sort' => array( // 排序
//             //   'created' => 'desc'
//        // )
//     )
// );
$rtn = $client->search($params);
if($analyze != 1){
    $return_data = array();
    foreach ($rtn['hits']['hits'] as $v => $k){
        $return_data[] = $k['_source'];
    }
    $rtn = $return_data;
}


//echo '<pre>';
//print_r($rtn);
//echo '</pre>';
echo json_encode($rtn);
exit();
//die('FILE:' . __FILE__ . '; LINE:' . __LINE__);

/*
Array(
    [took] => 3                                                         // 整个搜索请求花费的毫秒数
    [timed_out] =>                                                      // 查询超时与否
    [_shards] => Array(
            [total] => 5                                                // 参与查询的分片数
            [successful] => 5                                           // 成功数
            [skipped] => 0
            [failed] => 0                                               // 失败数
        )
    [hits] => Array(
            [total] => 2                                                // 匹配到的文档总数
            [max_score] => 0.6931472                                    // 指的是所有文档匹配查询中_score的最大值
            [hits] => Array(
                    [0] => Array(
                            [_index] => mumayi                          // 索引（库）
                            [_type] => mumayi_soft                      // 类型（表）
                            [_id] => 4
                            [_score] => 0.6931472                       // 相关性得分(relevance score)，它衡量了文档与查询的匹配程度
                            [_source] => Array(                         // 数据
                                    [appid] => 995595
                                    [type] => 3
                                    [title] => 招财锁 1545454
                                    [packagename] => com.anroid.mylockscreen
                                    [status] => 2
                                )
                        )

                    [1] => Array(
                            [_index] => mumayi
                            [_type] => mumayi_soft
                            [_id] => 1
                            [_score] => 0.25316024
                            [_source] => Array(
                                    [appid] => 995595
                                    [type] => 3
                                    [title] => 招财锁
                                    [packagename] => com.anroid.mylockscreen
                                    [status] => 2
                                )
                        )
                )
        )
)
 */
