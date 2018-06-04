<?php
//ini_set("display_errors", "On");
//error_reporting(E_ALL | E_STRICT);
header('Content-Type:text/html;charset=utf-8');
require_once("sphinxapi.php");
include dirname(__FILE__)."/include/function.php";
define('CACHE_DIR',dirname(__FILE__)."/cache/");
require_once("comm.php");
require_once("developersql.class.php");
require_once("developer.master.class.php");
require_once('redis/redis.softinfo.class.php');
require_once('redis/redis.archives.class.php');

//搜索关键词
$temkeyword = Sql_Inject(injectCheck(htmlspecialchars(strip_tags(trim(urldecode(urldecode($_REQUEST['q'])))))));
$sqlKeywords = array("/\badd\b/i","/\balter\b/i","/\bupdate\b/i","/\bdelete\b/i","/\bselect\b/i","/\band\b/i","/\bor\b/i","/\bbetween\b/i","/\bgroup\b/i","/\bdrop\b/i","/\bfrom\b/i","/\bhaving\b/i","/\border\b/i","/\blike\b/i","/\bchange\b/i","/\blimit\b/i","/\bdistinct\b/i","/\'/","/\"/","/%27/i","/0x27/i","/\bmid/i","/\bdatabase\(\)/i","/\bconcat/i","/char\(.*?\)/i","/%/","/\bwhere\b/i");
$sqlReplacement = "";
//$string = "1%'  and  mid(database(),1,1)  in (char(97),char(98),char(99),char(100),char(101),char(102),char(103))   and 1='1";
$keyword = preg_replace($sqlKeywords,$sqlReplacement,$temkeyword);

//非法词
$banArr = include_once('ban.php');
if(in_array($keyword,$banArr)){
    $jsondata['status'] = 0;
    $jsondata['message'] = '非法输入！';
    $result = json_encode($jsondata);
    echo $result;
    exit();
}

if(strlen($keyword) >= 45){
    $jsondata['status'] = 0;
    $jsondata['message'] = '输入不合法！';
    $result = json_encode($jsondata);
    echo $result;
    exit;
}
preg_match("/\?|？/",$keyword,$specialMatch);
if($specialMatch){
    //ShowMsg("非法输入！","-1");
    $jsondata['status'] = 0;
    $jsondata['message'] = '非法词，无法搜索！';
    $result = json_encode($jsondata);
    echo $result;
    exit();
}

$keyword = str_replace(' ', 'kgspace', $keyword);
//屏蔽藏语和维语搜索
$isMatch = preg_match('/^(?!_)[A-Za-z0-9_\x{4e00}-\x{9fa5}]+$/u',$keyword);
if(!$isMatch){
    $jsondata['status'] = 0;
    $jsondata['message'] = '非法词，无法搜索！';
    $result = json_encode($jsondata);
    echo $result;
    //echo "非法词，无法搜索！";
    exit();
}
$keyword = str_replace('kgspace', ' ', $keyword);

if(strtolower($keyword) == "1233go"){
    $result = array("data" => array("id"=>"931552"));
    echo json_encode($result);
    exit;
}
if(strtolower(str_replace(" ","",$keyword)) == "eclite"){
    $result = array("data" => array("id"=>"519222"));
    echo json_encode($result);
    exit;
}
if(strtolower($keyword) == "adsafe"){
    $result = array("data" => array("id"=>"888731"));
    echo json_encode($result);
    exit;
}
if(strtolower($keyword) == "233fun"){
    $result = array("data" => array("id"=>"990037"));
    echo json_encode($result);
    exit;
}
$nocache = (int)Sql_Inject(trim(strip_tags(htmlspecialchars($_REQUEST['nocache']))));
$keyword = Sql_Inject(FilterSearch(stripslashes(strip_tags($keyword))));
$jsoncallback = htmlspecialchars(FilterSearch($_REQUEST['jsoncallback']));//getJSON('xxx.com?callback=?') 中的callback

//分类
$cid = htmlspecialchars(FilterSearch(strip_tags($_REQUEST['c'])));

//分页
$page = (int)htmlspecialchars($_REQUEST['p']);

//数据显示偏移量(一页显示数据的条数)
$offset = (int)((htmlspecialchars($_REQUEST['f']))?htmlspecialchars($_REQUEST['f']):10);

//过滤是游戏还是应用
$t = (int)htmlspecialchars($_REQUEST['t']);

if($page <= 1){
    $page = 1;
    $from = 0;
}else{
    $from = ($page-1)*$offset;
}

//统计每天搜索总次数
$file = 'total.txt';
$current = (int)file_get_contents($file);
$person = $current+1;
file_put_contents($file, $person, LOCK_EX);

//过滤关键词
$banwordArr = array('UPDATEXML','SELECT','EXTRACTVALUE','INSERT','UPDATE','DELETE','大麻','可卡因','口袋女友','触摸女孩','乌苏出售冰毒【QQ1040592】','冰毒','殇情少东','淫唐传','奴妻','凌辱女友','世爵娱乐','杏彩平台');

$badwordStr = array_combine($banwordArr,array_fill(0,count($banwordArr),'kylingood'));

$newstr = strtr($keyword, $badwordStr);

$pos = strpos('kylingood', $newstr);

//模式分隔符后的"i"标记这是一个大小写不敏感的搜索
if (preg_match("/kylingood/i", $newstr)) {
    //$pos = strpos('kylingood', $newstr);
    //if ($pos !== false) {
    $jsondata['status'] = 0;
    echo $jsondata['message'] = '输入需要搜索的关键词,含有违法信息！';
    $result = json_encode($jsondata);

    //解决ajax跨域搜索
    if($jsoncallback){
        echo ($jsoncallback."(".$result.")");//加粗部分是要注意的
    }else{
        echo $result;
    }
    exit;
}



$rootdir = CACHE_DIR.date('Y').'/'.date('m').'/';
//创建目录
mkdirs($rootdir);
$filenames = $rootdir.md5($keyword).$page.$cid.$offset."_es.txt";
$newtime = (time()-filemtime($filenames));
$times = 900;

//如果文件存在或文件生成时间差小于1个小时，则生成文件
//if((!file_exists($filenames)) || ($newtime >= $times) || $nocache){
if(true){


    //统计搜索关键词
    $searchword = addslashes($keyword);
    if($searchword){
        /*
        $sql="SELECT aid FROM `mumayi_search_word` where keyword='$searchword'";
        $result = $Developerdb->fetch_first($sql);
        $aid = $result['aid'];
        //如果存在记录，就增加一次搜索次数
        if($aid){
            $sql = "UPDATE `mumayi_search_word`  SET `count` = `count`+1 where aid='$aid'";
            $re = $MasterDB->query($sql);
        }else{
            //如果没有记录，则直接插入
            $lasttime = time();
            $sql="INSERT INTO `mumayi_search_word` (`aid`, `keyword`, `count`, `lasttime`) VALUES (NULL,'$searchword','1', '$lasttime');";
            $MasterDB->query($sql);
        }*/
    }


    /**********************解决拼音搜索初级版***********************/
    //$keyword = 'mmy';
    preg_match_all("/[\x{4e00}-\x{9fa5}]+/u",$keyword, $isArr);
    //如果是纯英文或拼音，则直接like 搜索
    if(count($isArr[0])<=0){
        //if(false){

        //统计每天搜索拼音总次数
        $file = 'pinyintotal.txt';
        $current = (int)file_get_contents($file);
        $person = $current+1;
        file_put_contents($file, $person, LOCK_EX);

        /******编辑后台自定义控制搜索ID列表，优化搜索精确性*********/
        $search_array = require_once('include/searchcache/searchword.php');
        //$keys = md5($keyword);
        $keys = md5(strtolower($keyword));//解决大小写不统一的情况

        //如果
        if($page <= 1 ){
            if (array_key_exists($keys,$search_array)) {
                $customString = $search_array[$keys];//ID列表串
                $customsum = count(array_filter(explode(',',$customString)));
            }
        }
        /******编辑后台自定义控制搜索ID列表，优化搜索精确性*********/

        //如果文件存在或文件生成时间差小于1个小时，则生成文件
        if((!file_exists($filenames)) || ($newtime>=$times) || $nocache){

            //$sql = "SELECT id FROM `mumayi_search_soft` WHERE `pingyinword` LIKE '%$keyword%' order by `softweight` desc limit $from,$offset";

            $sql = "SELECT id FROM `mumayi_search_soft` WHERE `pingyinword` LIKE '%$keyword%' AND `online`=1 order by `download` desc limit $from,$offset";

            $query = $Developerdb->query($sql,'',$filenames);
            $data = array();
            while($row = $Developerdb->fetch_array($query)) {
                $data[] = $row['id'];
            }
            $idString = implode(',',$data);
            $idString = rtrim($idString,',');
            //重新计算总量和分页数
            $sqlone = "SELECT count(id) as total FROM `mumayi_search_soft` WHERE `pingyinword` LIKE '%$keyword%'  Limit 0,1";
            $result = $Developerdb->fetch_first($sqlone);
            $sum = $result['total'];


            $sum = $sum + $customsum;
            $sumpage = ceil($sum/$offset);

            $jsondata = array();
            $jsondata['status'] = 1;

            $idString = trim($idString,',');
            if($customString){
                $jsondata['data']['id'] = trim($customString.','.$idString,',');
            }else{
                $jsondata['data']['id'] = trim($idString,',');
            }

            if($page == 1){
                $realsum = count(explode(',',$idString));
                //解决第一页，统计不准问题
                if($realsum <= $sum) $sum = $realsum;
            }

            $jsondata['data']['sum'] = $sum;
            $jsondata['data']['sumpage'] = $sumpage;
            $jsondata['message'] = '恭喜，搜索成功！';
            $result = json_encode($jsondata);

            $re = file_put_contents($filenames, $result);
            @chmod($filenames, 0775);

            //搜索缓存
        }else{
            $result = file_get_contents($filenames);
        }

        //解决ajax跨域搜索
        if($jsoncallback){
            echo ($jsoncallback."(".$result.")");//加粗部分是要注意的
        }else{
            echo $result;
        }
        exit;
    }
    /**********************解决拼音搜索结束*************************/



    $jsondata = array();

    if($keyword=='请输入关键词...'||$keyword==''){
        $jsondata['status'] = 0;
        $jsondata['message'] = '请输入您需要搜索的关键词！';
        $result = json_encode($jsondata);

        //解决ajax跨域搜索
        if($jsoncallback){
            echo ($jsoncallback."(".$result.")");//加粗部分是要注意的
        }else{
            echo $result;
        }
        exit;
    }

    //过滤关键词
    $banwordArr = array('口袋女友','触摸女孩','乌苏出售冰毒【QQ1040592】','冰毒','殇情少东','淫唐传','奴妻','凌辱女友');
    if(in_array($keyword,$banwordArr)){
        $jsondata['status'] = 0;
        $jsondata['message'] = '输入需要搜索的关键词,含有违法信息！';
        $result = json_encode($jsondata);

        //解决ajax跨域搜索
        if($jsoncallback){
            echo ($jsoncallback."(".$result.")");//加粗部分是要注意的
        }else{
            echo $result;
        }
        exit;
    }

    // es搜索接口
    $url = "http://39.105.83.21/elasticsearch/es.php?myindex=mmy&mytype=mumayi_soft&p=$page&pagenum=$offset&analyze=1&keyword=".urlencode($keyword);
    $result = fetchSoftData($url);
    $result = json_decode($result, true);
    $sum = $result['hits']['total'];
//    var_dump($result, $sum);die;
    /******编辑后台自定义控制搜索ID列表，优化搜索精确性*********/
    $search_array = require_once('include/searchcache/searchword.php');
    //$keys = md5($keyword);
    $keys = md5(strtolower($keyword));//解决大小写不统一的情况

    //如果
    if($page <= 1 ){
        if (array_key_exists($keys,$search_array)) {
            $customString = $search_array[$keys];//ID列表串
            $sum = count(array_filter(explode(',',$customString))) + $sum;
        }
        /******编辑后台自定义控制搜索ID列表，优化搜索精确性*********/
    }

    $sumpage = ceil($sum/$offset);
    if($sum==0){
        $jsondata['status'] = 0;
        $jsondata['message'] = '未搜索到相关软件。';
        $result = json_encode($jsondata);
        //解决ajax跨域搜索
        if($jsoncallback){
            echo ($jsoncallback."(".$result.")");//加粗部分是要注意的
        }else{
            echo $result;
        }
        exit;
    }else{
        if(is_array($result['hits']['hits'])){

            if($keyword=='三国志国战版' || $keyword=='三国志' || $keyword=='国战版' ){
                $idarr[] = 531497;
            }

            if($keyword=='创业'){
                //$idarr[] = 520520;
            }

            if($keyword=='通话录音'){
                //$idarr[] = 246401;
            }

            if($keyword=='优酷' || $keyword=='看电影' || $keyword=='看电视' ){
                //$idarr[] = 30752;
            }

            /**********咪咕合作规则********/

            if($keyword=='彩铃'  || $keyword=='铃声' || $keyword=='剪辑歌曲' ){
                $idarr[] = 269024;
            }

            if($keyword=='K歌'  || $keyword=='唱歌' || $keyword=='KTV'  || $keyword=='ktv'  || $keyword=='Ktv'  ){
                $idarr[] = 279175;
            }

            if($keyword=='音乐资讯'  || $keyword=='娱乐新闻'){
                $idarr[] = 323315;
            }

            if($keyword=='高清MV'  || $keyword=='高清mv'   || $keyword=='高清Mv' ){
                $idarr[] = 263105;
            }
            /**********咪咕合作规则********/

            foreach ( $result['hits']['hits'] as $doc => $docinfo ) {
                $idarr[] = $docinfo['_id'];
            }

            /**********淘宝合作规则********/
            if($keyword=='漂流瓶'){
                array_splice($idarr, 8, 0, "537219");
            }

            /**********咪咕合作规则********/
            if($keyword=='音乐'  || $keyword=='播放器' || $keyword=='音乐资讯' ){

                array_splice($idarr, 2, 0, "263105");
            }

            /**********淘宝合作规则********/
            if($keyword=='阅读'  || $keyword=='淘宝'){
                array_splice($idarr, 3, 0, "97078");
            }
            /**********淘宝合作规则********/

            //如果存在编辑手动控制搜索结果ID，先去掉es出来的ID值
            if($customString){
                $customArr = array_filter(explode(',',$customString));
                foreach($customArr as $ids){
                    $key = array_search($ids, $idarr);
                    if($key){
                        unset($idarr[array_search($ids, $idarr)]);
                    }
                }
            }

            if(is_array($idarr)){
                $ids = implode(',',$idarr);
                // 只取当前在线软件
                $nowtime = time();
                $todaytime = strtotime(date('Y-m-d'));
                $ago_todaytime = $todaytime-5184000; //(3600*24*30*2)
                if($customString){
                    $newsql = "Select id,icon,created,title,status,versionname,introduction,score,softsize,frontdownload,recommend from mumayi_soft where id IN ( $ids ) AND created<='$nowtime' AND adminstatus!=20 AND id NOT IN ($customString) ";
                }else{
                    $newsql = "Select id,icon,created,title,status,versionname,introduction,score,softsize,frontdownload,recommend from mumayi_soft where id IN ( $ids ) AND created<='$nowtime' AND adminstatus!=20 ";
                }
                $newquery = $Developerdb->query($newsql,'',$filenames);
                $downloadArr = $scoreArr = array();
                set_time_limit(0);
                while($row = $Developerdb->fetch_array($newquery)) {
                    $id = $row['id'];
                    if($row['status'] > 0){
                        /*
                         * 第一段：更新时间2个月之内，取下载量大的靠前排序
                         * 第二段：更新时间2个月之外的，按照更新时间由近及远排序
                         */
                        if($row['created'] >= $ago_todaytime){
                            $downloadArr[$id] = $row['frontdownload'];
                        }else{
                            $updateArr[$id] = $row['created'];
                        }
                    }
                }
            }
        }

        // 排序
        arsort($downloadArr);
        arsort($updateArr);
        $sortID = array_merge(array_keys($downloadArr),array_keys($updateArr));
    }


    //从第二页中过滤掉，编辑手动控制的数据
    if($page <= 1 ){
        $idString = implode(',', $sortID);
        //如果是第一页，把编辑控制的控制的软件ID加上
        $idString = trim($customString.','.$idString,',');
    }else{
        //sphinx 查询到的软件ID
        $idArr = $sortID;
        //编辑控制的ID列表串
        $bianjiString = $search_array[$keys];
        //编辑控制的数组ID
        $bianjiArr = array_filter(explode(',',$bianjiString));
        $resultArr = array_diff($idArr, $bianjiArr);
        $idString = implode(',',$resultArr);
        //重新计算总量和分页数
        $sum = count(array_filter(explode(',',$bianjiString))) + $sum;
        $sumpage = ceil($sum/$offset);
    }
    $idString = trim($idString,',');

    //解决第一页，统计不准问题
    if($page == 1){
        $realsum = count(explode(',',$idString));
        if($realsum <= $sum) $sum = $realsum;
    }

    $jsondata = array();
    $jsondata['status'] = 1;
    $jsondata['data']['id'] = $idString;
    $jsondata['data']['sum'] = $sum;
    $jsondata['data']['sumpage'] = $sumpage;
    $jsondata['message'] = '恭喜，搜索成功！';
    $result = json_encode($jsondata);
    $re = file_put_contents($filenames, $result);
    @chmod($filenames, 0775);
}else{
    $result = file_get_contents($filenames);
}


//解决ajax跨域搜索
if($jsoncallback){
    echo ($jsoncallback."(".$result.")");//加粗部分是要注意的
}else{
    echo $result;
}

exit;

