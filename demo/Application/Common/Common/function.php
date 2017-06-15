<?php
/***************************************************自己加的*********************************************************/
function ri($str){
	exit(var_dump($str));
}

//去掉图片的域名
function getImgPath($url,$prefixThumb='thumb_'){
	//$domain = ;
	$path = str_replace($prefixThumb,'',str_replace(C('FILE_URL'),"",$url)); //清除域名，及thumb_
	$arr = explode('/',$path);
	$filename = $arr[count($arr)-1];
	$fileinfo = explode('.',$filename);
	$fileinfo['path'] = C('SAVE_PATH').str_replace($filename,'',$path);
	//$filename = $filename[1];
	return $fileinfo;
}

function toHtml($str){
	$str = nl2br($str);
	$str = str_replace(' ',"&nbsp;",$str);
	return $str;
}

function unHtml(){
	$str = str_replace('&nbsp;',' ',$str);
	$str = str_replace('<br />','\n',$str);
	
}

/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其他编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
{
    if(function_exists("mb_substr"))
        return mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        return iconv_substr($str,$start,$length,$charset);
    }
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    if($suffix) return $slice."…";
    return $slice;
}



function sendmail($subject,$body,$to,$toname,$from = "",$fromname = '中华网',$altbody = '中华网的邮件',$wordwrap = 80,$mailconf = ''){
	Vendor('phpmail.class#phpmailer');
	$mail             = new PHPMailer();
	$mail->IsSendmail(); 
	$mail->SMTPDebug  = 2;                   // enables SMTP debug // 1 = errors and messages// 2 = messages only
	$from = "admin@".DOMAIN;
	$fromname = C('SITE_TITLE');
	$mail->SetFrom($from, $fromname);
	//$mail->AddReplyTo($to,$toname);
	$mail->CharSet = 'UTF-8';
	$mail->Encoding = 'base64';
	$mail->Subject    = $subject;
	$body             = eregi_replace("[\]",'',$body);
	//$mail->AltBody    = "AltBody"; // optional, comment out and test
	$mail->MsgHTML($body);
	$mail->AddAddress($to, $toname);
	//$mail->AddAttachment("images/phpmailer.gif");      // attachment
	//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
	if(!$mail->Send()) {
	  //echo "Mailer Error: " . $mail->ErrorInfo;
	  return false;
	} else {
	  return true;
	}
}

function sendmail_smtp($subject,$body,$to,$toname,$from = "",$fromname = '中华网',$altbody = '中华网的邮件',$wordwrap = 80,$mailconf = ''){
	Vendor('phpmail.class#phpmailer');
	$mail             = new PHPMailer();
	$mail->IsSMTP(); // telling the class to use SMTP
	$mail->SMTPDebug  = 2;                   // enables SMTP debug // 1 = errors and messages// 2 = messages only
	
	$mail->SMTPAuth   = true;                  // enable SMTP authentication
	$mail->Host       = C('M_HOST'); // sets the SMTP server
	$mail->Port       = 25;                    // set the SMTP port for the GMAIL server
	$mail->Username   = C('M_USER'); // SMTP account username
	$mail->Password   = C('M_PASSWORD');        // SMTP account password
	
	$from = !strpos(C('M_USER'),'@') ? C('M_USER').'@'.C('M_DOMAIN') : 'qihjn@163.com';
	$mail->SetFrom($from, $fromname);
	
	//$mail->AddReplyTo($to,$toname);
	$mail->CharSet = 'UTF-8';
	$mail->Encoding = 'base64';

	$mail->Subject    = $subject;
	$body             = eregi_replace("[\]",'',$body);
	//$mail->AltBody    = "AltBody"; // optional, comment out and test
	
	$mail->MsgHTML($body);
	
	$mail->AddAddress($to, $toname);
	
	//$mail->AddAttachment("images/phpmailer.gif");      // attachment
	//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
	
	if(!$mail->Send()) {
	  //echo "Mailer Error: " . $mail->ErrorInfo;
	} else {
	  //echo "Message sent!";
	}
}


//================== 用户相关 start ================//
/**
 * 保存用户登录凭证
 */
function setUserAuth($u){
	//$u = array();
	if (C('AUTH_STORE_WAY') == 'cookie') {
		//var_dump($u);
		
		import("ORG.Util.Cookie");
		//var_dump(Cookie::b("uid", $u['uid']));
		//$id = Cookie::get(C('USER_AUTH_KEY'));
		cookie("username",$u['username']);
		cookie("uid", \Think\Crypt::encrypt($u['uid'],C('crypt_key')));
		cookie('type', $u['type']);
	}else{
		$_SESSION[C('USER_AUTH_KEY')] = $id;
		$_SESSION["username"] = $u['username'];
		$u['uid'] = $_SESSION["uid"];
		$u['utype'] = $_SESSION['utype'];
	}
}

/**
 * 获取用户登录凭证信息
 */
function getUserAuth(){
	$u = array();
	if (C('AUTH_STORE_WAY') == 'cookie') {
		//$id = cookie(C('USER_AUTH_KEY'));
		$u['username'] = cookie("username","");
		$u['uid'] = \Think\Crypt::decrypt(cookie('uid',""),C('crypt_key'));
		$u['type'] = cookie('type',"");
		//var_dump($_COOKIE);
		//exit;
	}else{
		$id = $_SESSION[C('USER_AUTH_KEY')];
		$u['username'] = $_SESSION["username"];
		$u['uid'] = $_SESSION[C('USER_AUTH_KEY')];
		$u['type'] = $_SESSION['type'];
	}
	
	return $u;
}

/**
 * 获取用户登录凭证信息
 */
function clearUserAuth(){
	$u = array();
	if (C('AUTH_STORE_WAY') == 'cookie') {
		cookie("username",null);
		cookie("uid",null);
	}else{
		unset($_SESSION[C('USER_AUTH_KEY')]);
		unset($_SESSION["username"]);
		unset($_SESSION[C('USER_AUTH_KEY')]);
		unset($_SESSION['utype']);
	}
	return $u;
}



/**
 * 得到登录的用户id
 */
function getUserId(){
	if (C('AUTH_STORE_WAY') == 'cookie') {
		$id = Cookie::get(C('USER_AUTH_KEY'));
		
	}else{
		$id = $_SESSION[C('USER_AUTH_KEY')];
	}
	//echo $_SESSION[C('USER_AUTH_KEY')];
	return $id;
}

/**
 * 得到登录的用户信息
 */
function getUserInfo(){
	//个人简历
	//企业信息
	//学校信息
	$sql = "";
	$user = D('Member');
	if ($id = getUserId()) {
		return $user->find(getUserId());
	}
}
//================== 用户相关 end ================//


/*function get_client_ip() {
	if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
		$ip = getenv ( "HTTP_CLIENT_IP" );
	else if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
		$ip = getenv ( "HTTP_X_FORWARDED_FOR" );
	else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
		$ip = getenv ( "REMOTE_ADDR" );
	else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
		$ip = $_SERVER ['REMOTE_ADDR'];
	else
		$ip = "unknown";
	return ($ip);
}*/

function IP($ip = '', $file = 'UTFWry.dat') {
	$_ip = array ();
	if (isset ( $_ip [$ip] )) {
		return $_ip [$ip];
	} else {
		import ( "ORG.Net.IpLocation" );
		$iplocation = new IpLocation ( $file );
		$location = $iplocation->getlocation ( $ip );
		$_ip [$ip] = $location ['country'] . $location ['area'];
	}
	return $_ip [$ip];
}

//============== ip 获取城市函数===============//
function ip2num($ip){
	$ipadd = explode('.',$ip);
	return intval($ipadd[0])*256*256*256 + intval($ipadd[1])*256*256 + intval($ipadd[2]*256) + intval($ipadd[3]);
}

/*$ipnum 运算之后的数字*/
function getcitybydb($ip){
	$ipnum = ip2num($ip);
	$m = M('Ip');
	$r = $m->query("select city,province from mmm_ip where $ipnum>=ip1 and $ipnum<=ip2 limit 1");
	
	$r = $r[0];
	//echo "select city,province from ip where $ipnum>=ip1 and $ipnum<=ip2 limit 1"; //select city,province from p8_fenlei_ip where ip1<= 3729367335 and ip2>=3729367335 limit 1
	if(!is_array($r)){
		//未找到，返回默认城市
		$r['province'] = '上海'; 
		$r['city'] = '上海';
	}
	return $r;
}

/**
 * 根据ip得到城市
 * @param string $ip 
 */
function getcity($ip = ''){
	//global $onlineip;
	$ip || $ip = get_client_ip();
	if($_COOKIE["IP_province"] && $_COOKIE["IP_city"]){
		$r['province'] = $_COOKIE['IP_province'];
		$r['city']  = $_COOKIE['IP_city'];
		return $r;
	}else{
		$r = getcitybydb($ip);
		setcookie("IP_province",$r['province'],time()+7*86400);
		setcookie("IP_city",$r['city'],time()+7*86400);
		return $r;
	}
}
//============== ip 获取城市函数  end ===============//


/**
 *高亮关键字
 */
function hightLightKeyword($str,$keyword){
	$replaceStr = "<span style=' background-color:#FF0; '>$keyword</span>";
	//echo $str;
	//exit(str_ireplace("s","2","SB"));
	return str_ireplace($keyword,$replaceStr,$str);
}

/**
 *分割字符，返回有效数组
 */
function validExplode($separator,$str){
		
	if($str) {
		$re = array();
		$arr = explode($separator, $str);
		foreach($arr as $v) {
			if($v) $re[] = $v;
		}
		return $re;
	}
}
/**
 * 分割字符，返回有效数组
 * 与str_split 类似
 */
function strToChar($str){
	//$str = 'string';
	return preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
	//print_r($chars); //结果：Array ( [0] => s [1] => t [2] => r [3] => i [4] => n [5] => g ) 
}

//==================格式化数据 模板中使用  start ====================//
function img($v){
	if($v){
		$arr = parse_url($v);
		if(strtolower($arr['scheme']) != 'http'){
			return C('IMG_URL').$v;
		}
		return $v;
	}else{
		return C('DEFAULT_IMG');
	}
}

function getEnumTitle($id,$egroup='job_category',$way='evalue'){
	//echo $egroup;exit;
	$ids = explode(',',$id);
	$titles = '';
	$e = D('Enum');
	foreach($ids as $v){
		
		if($v!=''){
			$titles .= $e->getTitle($v,$egroup,$way).',';
		}
	}
	
	return substr($titles,0,-1);
}
/**
 * 获取男女文字
 * @param $v
 */
function sex($v) {
	if($v == 0) return "女";
	if($v == 1) return "男";
	return "";
	//return $v ? "男" : "女";
}

/**
 * dateFormat 格式化日期
 * @param unknown_type $time
 * @param unknown_type $format
 */
function df($time,$format = 'Y-m-d'){
	return date($format,$time);
}

/**
 * 得到统计查询结果数量的sql语句
 * @param unknown_type $sql
 */
function getCountSql($sql) {
	return preg_replace("/(select) (.*) (from .*)/i","\$1 count(id) \$3",$sql);;
}

/**
 * 截取职位类别的字符串
 * @param $cateid 类别
 * @param $n 截取长度
 */
function cateSub($cateid,$n){
	return msubstr(getEnumTitle($cateid),0,$n);
}

/**
 * 获取省的名称
 * @param string $code 省的拼音
 * @return 
 */
function getProvince($code){
	if ($code) { //code优先
		$key = 'province.'.$code; //code为省拼音
		$province = C($key); //省的汉字名称
		setcookie("IP_province",$province,time()+7*86400);
		//$_COOKIE["IP_province"] = $province;
	}elseif($_COOKIE["IP_province"]){ //cookie其次
		$province = $_COOKIE["IP_province"];
	}else{ //根据ip获取最后
		$ip = '60.190.28.48';
		$ipcity = getcity($ip);
		$province = $ipcity['province'];
	}
	return $province;
}

/**
 * 生成公司显示url
 * @param unknown_type $id
 */
function curl($id){
	return "/company/show_$id.html";
	
}

//当然下面的更绝,不过好像违背了php与html分离原则，但用起来确实很方便。没有class,id其它属性。
function jurl2($id,$title,$taget="_self"){
	
	return "<a href=\"/job/show_$id.html\" target=\"$taget\" title=\"$title\">{$title}</a>";
}

/**
 * 生成职位显示url
 * @param unknown_type $id
 */
function jurl($id){
	return "/job/show_$id.html";
}

/**
 * 生成简历显示url
 * @param unknown_type $id
 */
function rurl($id){
	return "/resume/$id/show.html";
}

/**
 * 获取省名称通过key
 * @param $code
 */
function getProvinceByKey($code){
	$area = D('Area');
	$province = $area->getProvince (); //省列表
	$province = $province[$code];
	if (!$province) {
		return '中国';
	}
}

/*得到行业分类的列表*/
function getIndustryBigClass($key){
	if ($bigClass == -1) {
		return '所有';
	}
	$e = D('Enum');
	return $e->getTitle($v[$key]);
	
}

//==================格式化数据  end ====================//

/**
 * 区域条件生成
 * @param unknown_type $province
 */
function areaCondition($province){
	return array(array("like","%$province%"),array("like",'全国'),array("eq",''),'or');//区域条件，多次使用，后台公司，简历列表，前台招聘首页，搜索页
}



function zhjson($v){
	if(is_array($v)){
		foreach($v as $key =>$value){
			$v[$key]=zhjson($value);
		}
		return $v;
	}else{
		return iconv("gb2312","utf-8",$v);
	}
}

function getProvincePingying($province){
	$tmp = explode(',', $province); //'江苏/南京,浙江/杭州'
	$area = array();
	if(is_array($tmp)) { // array('江苏/南京','浙江/杭州')
		foreach($tmp as $v) {
			if($v = trim($v)) {
				$t = explode('/', $v);
				$area[] = $t[0];
			}
		}
	}
	if (count($area) == 1) {
		//return $area[0];
	}
	$province = C('province');
	//$arr = array();
	foreach($province as $k => $v){
		foreach($area as $p){
			//echo "$p---$v";exit;
			if(false !== strpos($p,$v)){
				$arr[$k] = $p;
				break;
			}
		}
	}
	
	return $arr;
}

/**
  * 登录后的登录框内容
  */
function loginedbar(){
	//ECHO Cookie::get(C('USER_AUTH_KEY'));
	$r = getUserInfo();
	if(is_array($r)){
		$v = new View();
		$v->assign('u',$r);
		//echo __FILE__;
		//echo IROOT.'/User/Tpl/default/Public/logined';
		echo $v->fetch('../21mmm_tp/User/Tpl/default/Public/logined.html');
	}
}


//============ 旧程序 cookie函数============
function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false) {
	//global $cookiepre, $cookiedomain, $cookiepath, $_SERVER;
	$cookiepre = 'FSQ_';
	$cookiedomain = DOMAIN;
	$cookiepath = '/';
	$timestamp=time();
	$var = ($prefix ? $cookiepre : '').$var;
	if($value == '' || $life < 0) {
		$value = '';
		$life = -1;
	}
	$life = $life > 0 ? $timestamp + $life : ($life < 0 ? $timestamp - 31536000 : 0);
	$path = $httponly && PHP_VERSION < '5.2.0' ? "$cookiepath; HttpOnly" : $cookiepath;
	$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
	//echo $var; echo "--$value";
	if(PHP_VERSION < '5.2.0') {
		setcookie($var, $value, $life, $path, $cookiedomain, $secure);
	} else {
		setcookie($var, $value, $life, $path, $cookiedomain, $secure, $httponly);
	}
}

function clearcookies() {
	foreach(array('sid', 'auth', 'visitedfid', 'onlinedetail', 'loginuser', 'activationauth') as $k) {
		dsetcookie($k);
	}
}

function g_cookie($var){
	$cookiepre = 'FSQ_';
	$var=$cookiepre.$var;
	return $_COOKIE[$var];
}




//公共函数
function toDate($time, $format = 'Y-m-d H:i:s') {
	if (empty ( $time )) {
		return '';
	}
	$format = str_replace ( '#', ':', $format );
	return date ($format, $time );
}


// 缓存文件
function cmssavecache($name = '', $fields = '') {
	$Model = D ( $name );
	$list = $Model->select ();
	$data = array ();
	foreach ( $list as $key => $val ) {
		if (empty ( $fields )) {
			$data [$val [$Model->getPk ()]] = $val;
		} else {
			// 获取需要的字段
			if (is_string ( $fields )) {
				$fields = explode ( ',', $fields );
			}
			if (count ( $fields ) == 1) {
				$data [$val [$Model->getPk ()]] = $val [$fields [0]];
			} else {
				foreach ( $fields as $field ) {
					$data [$val [$Model->getPk ()]] [] = $val [$field];
				}
			}
		}
	}
	$savefile = cmsgetcache ( $name );
	// 所有参数统一为大写
	$content = "<?php\nreturn " . var_export ( array_change_key_case ( $data, CASE_UPPER ), true ) . ";\n?>";
	file_put_contents ( $savefile, $content );
}

function cmsgetcache($name = '') {
	return DATA_PATH . '~' . strtolower ( $name ) . '.php';
}

//css样式状态
function getStatus2($status, $imageShow = true) {
	switch ($status) {
		case 0 :
			$showText = '禁用';
			$showImg = '<span class="label">禁用</span>';
			break;
		case 2 :
			$showText = '待审';
			$showImg = '<span class="label label-warning">待审</span>';
			break;
		case - 1 :
			$showText = '删除';
			$showImg = '<span class="label label-important">删除</span>';
			break;
		case 1 :
		default :
			$showText = '正常';
			$showImg = '<span class="label label-success">正常</span>';

	}
	return ($imageShow === true) ?  $showImg  : $showText;

}
//图片样式状态
function getStatus($status, $imageShow = true) {
	switch ($status) {
		case 0 :
			$showText = '禁用';
			$showImg = '<IMG SRC="' . '' . '__SKIN__/Common/locked.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="禁用">';
			break;
		case 2 :
			$showText = '待审';
			$showImg = '<IMG SRC="' . '' . '__SKIN__/Common/prected.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="待审">';
			break;
		case - 1 :
			$showText = '删除';
			$showImg = '<IMG SRC="' . '' . '__SKIN__/Common/del.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="删除">';
			break;
		case 1 :
		default :
			$showText = '正常';
			$showImg = '<IMG SRC="' . '' . '__SKIN__/Common/ok.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="正常">';

	}
	return ($imageShow === true) ?  $showImg  : $showText;

}
function getDefaultStyle($style) {
	if (empty ( $style )) {
		return 'blue';
	} else {
		return $style;
	}

}


function getNodeName($id) {
	if (Session::is_set ( 'nodeNameList' )) {
		$name = Session::get ( 'nodeNameList' );
		return $name [$id];
	}
	$Group = D ( "Node" );
	$list = $Group->getField ( 'id,name' );
	$name = $list [$id];
	Session::set ( 'nodeNameList', $list );
	return $name;
}

function get_pawn($pawn) {
	if ($pawn == 0)
		return "<span style='color:green'>没有</span>";
	else
		return "<span style='color:red'>有</span>";
}
function get_patent($patent) {
	if ($patent == 0)
		return "<span style='color:green'>没有</span>";
	else
		return "<span style='color:red'>有</span>";
}


function getNodeGroupName($id) {
	if (empty ( $id )) {
		return '未分组';
	}
	if (isset ( $_SESSION ['nodeGroupList'] )) {
		return $_SESSION ['nodeGroupList'] [$id];
	}
	$Group = D ( "Group" );
	$list = $Group->getField ( 'id,title' );
	$_SESSION ['nodeGroupList'] = $list;
	$name = $list [$id];
	return $name;
}

function getCardStatus($status) {
	switch ($status) {
		case 0 :
			$show = '未启用';
			break;
		case 1 :
			$show = '已启用';
			break;
		case 2 :
			$show = '使用中';
			break;
		case 3 :
			$show = '已禁用';
			break;
		case 4 :
			$show = '已作废';
			break;
	}
	return $show;

}

function showStatus($status, $id) {
	switch ($status) {
		case 0 :
			$info = '<a href="javascript:resume(' . $id . ')">恢复</a>';
			break;
		case 2 :
			$info = '<a href="javascript:pass(' . $id . ')">批准</a>';
			break;
		case 1 :
			$info = '<a href="javascript:forbid(' . $id . ')">禁用</a>';
			break;
		case - 1 :
			$info = '<a href="javascript:recycle(' . $id . ')">还原</a>';
			break;
	}
	return $info;
}

/**
 +----------------------------------------------------------
 * 获取登录验证码 默认为4位数字
 +----------------------------------------------------------
 * @param string $fmode 文件名
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function build_verify($length = 4, $mode = 1) {
	return rand_string ( $length, $mode );
}


function getGroupName($id) {
	if ($id == 0) {
		return '无上级组';
	}
	if ($list = F ( 'groupName' )) {
		return $list [$id];
	}
	$dao = D ( "Role" );
	$list = $dao->select ( array ('field' => 'id,name' ) );
	foreach ( $list as $vo ) {
		$nameList [$vo ['id']] = $vo ['name'];
	}
	$name = $nameList [$id];
	F ( 'groupName', $nameList );
	return $name;
}
function sort_by($array, $keyname = null, $sortby = 'asc') {
	$myarray = $inarray = array ();
	# First store the keyvalues in a seperate array
	foreach ( $array as $i => $befree ) {
		$myarray [$i] = $array [$i] [$keyname];
	}
	# Sort the new array by
	switch ($sortby) {
		case 'asc' :
			# Sort an array and maintain index association...
			asort ( $myarray );
			break;
		case 'desc' :
		case 'arsort' :
			# Sort an array in reverse order and maintain index association
			arsort ( $myarray );
			break;
		case 'natcasesor' :
			# Sort an array using a case insensitive "natural order" algorithm
			natcasesort ( $myarray );
			break;
	}
	# Rebuild the old array
	foreach ( $myarray as $key => $befree ) {
		$inarray [] = $array [$key];
	}
	return $inarray;
}

/**
	 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码
 * 默认长度6位 字母和数字混合 支持中文
	 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
	 +----------------------------------------------------------
 * @return string
	 +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
	$str = '';
	switch ($type) {
		case 0 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		case 1 :
			$chars = str_repeat ( '0123456789', 3 );
			break;
		case 2 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
			break;
		case 3 :
			$chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		default :
			// 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
			$chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
			break;
	}
	if ($len > 10) { //位数过长重复字符串一定次数
		$chars = $type == 1 ? str_repeat ( $chars, $len ) : str_repeat ( $chars, 5 );
	}
	if ($type != 4) {
		$chars = str_shuffle ( $chars );
		$str = substr ( $chars, 0, $len );
	} else {
		// 中文随机字
		for($i = 0; $i < $len; $i ++) {
			$str .= msubstr ( $chars, floor ( mt_rand ( 0, mb_strlen ( $chars, 'utf-8' ) - 1 ) ), 1 );
		}
	}
	return $str;
}
function pwdHash($password, $type = 'md5') {
	return hash ( $type, $password );
}

/**
 * 创建js公共变量文件
 */
function createJsPublicVar(){
	$jsVar = '
	var jsDomain = "'.DOMAIN.'"; 
	var jsImg = "'.C('IMG_URL').'";
	var jsPublic = "'.WEB_PUBLIC_PATH.'";
	';
	//echo $jsVar;exit;
	file_put_contents('./Public/Js/publicVar.js',$jsVar);
}

function chkSelected($v){
	if($v){
		echo 'checked="checked"';
	}
}

function showImg($v){
	if($v){
		echo '<img src="'.C('IMG_URL').$v.'" width="90" height="100"   />';
	}
}

function sltSelected($v){
	if($v){
		echo 'checked="checked"';
	}
}

//简单加密函数
function s_encrypt($str){
        $encrypt_key = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890./:-';
        $decrypt_key = 'GNZQTCOMBUHELPKDAFWXYIRVJSabcdefghijklmnopqrstuvwxyz3246708159:|#^';

        if (strlen($str) == 0) return false;

        for ($i=0; $i<strlen($str); $i++){
                for ($j=0; $j<strlen($encrypt_key); $j++){
                        if ($str[$i] == $encrypt_key[$j]){
                                $enstr .= $decrypt_key[$j];
                                break;
                        }
                }
        }

        return $enstr;
}

//简单解密函数（与php_encrypt函数对应）
function s_decrypt($str){
        $encrypt_key = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890./:-';
        $decrypt_key = 'GNZQTCOMBUHELPKDAFWXYIRVJSabcdefghijklmnopqrstuvwxyz3246708159:|#^';

        if (strlen($str) == 0) return false;

        for ($i=0; $i<strlen($str); $i++){
                for ($j=0; $j<strlen($decrypt_key); $j++){
                        if ($str[$i] == $decrypt_key[$j]){
                                $enstr .= $encrypt_key[$j];
                                break;
                        }
                }
        }

        return $enstr;
}
//以下为home所有
function cateTitle($id){
	$c = D('Category');
	return $c->getTitle($id);
}

/*检测用户登录*/
/*function chkUser(){
	if(Cookie::get(C('USER_AUTH_KEY'))){
		return true;
	}
}

function chkCuser(){
	if(Cookie::get('authId') && Cookie::get('utype') == 'unit'){
		return true;
	}
}

function chkPuser(){
	if(Cookie::get('authId') && Cookie::get('utype') == 'person'){
		return true;
	}
}

function chkSuser(){
	if(Cookie::get('authId') && Cookie::get('utype') == 'school'){
		return true;
	}
}*/


//产生随机数
function createRand(){
	$str=gettimeofday(1).rand();
	return str_replace(".","",$str);
}


//获取子元素
function getChild(&$arr, $pid){
	$child = array();
	foreach($arr as $k => $v){
		if($v['pid'] == $pid){
			$child[] = $v;
			unset($arr[$k]);
		}
	}
	return $child;
}

//字符裁剪 $str, $start=0, $length, $charset="utf-8", $suffix=true
function cutstr($str,$start=0, $length, $charset="utf-8", $suffix=true){
	//echo utf8_strlen($str);
	if(utf8_strlen($str) > $length){
		/*var_dump($str);
		var_dump($start);
		var_dump($length);
		var_dump(utf8_strlen($str));*/
		$str = msubstr($str, $start, $length, $charset, false);
		if($suffix){
			$str.="...";
		}
		//exit('x');
		//return $str;
	}
	return $str;
}

// 计算中文字符串长度
function utf8_strlen($string = null) {
	preg_match_all("/./us", $string, $match);
	return count($match[0]);
	//$str = ‘Hello,中国！’;echo ($zhStr);
	//$zhStr = ‘您好，中国！’;
	// 输出：6
	//echo utf8_strlen($str); // 输出：9
}

function filter_script($str){
	$str = preg_replace( "@<script(.*?)</script>@is", "", $str );
	$str = preg_replace( "@<iframe(.*?)</iframe>@is", "", $str );
	$str = preg_replace( "@<style(.*?)</style>@is", "", $str );
	return $str;
}


function do_post($url, $data) {
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt ( $ch, CURLOPT_POST, TRUE );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt ( $ch, CURLOPT_URL, $url );
	$ret = curl_exec ( $ch );
	
	curl_close ( $ch );
	return $ret;
}

function get_url_contents($url) {
	if (ini_get ( "allow_url_fopen" ) == "1")
		return file_get_contents ( $url );
	
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt ( $ch, CURLOPT_URL, $url );
	$result = curl_exec ( $ch );
	curl_close ( $ch );
	
	return $result;
}

function getExceptionTraceAsString($exception) {
	$rtn = "";
	$count = 0;
	foreach ($exception->getTrace() as $frame) {
		empty($frame['file']) && $frame['file'] = "[internal function]"; //空则赋值
		empty($frame['class']) || $frame['class'] = $frame['class']."->"; //空则不赋值，也就是非空才赋值，高手的写法，菜鸟的内心是无法理解的
		$args = "";
		if (isset($frame['args'])) {
			$args = array();
			foreach ($frame['args'] as $arg) {
				if (is_string($arg)) {
					$args[] = "'" . $arg . "'";
				} elseif (is_array($arg)) {
					$args[] = "Array";
				} elseif (is_null($arg)) {
					$args[] = 'NULL';
				} elseif (is_bool($arg)) {
					$args[] = ($arg) ? "true" : "false";
				} elseif (is_object($arg)) {
					$args[] = get_class($arg);
				} elseif (is_resource($arg)) {
					$args[] = get_resource_type($arg);
				} else {
					$args[] = $arg;
				}
			}
			$args = join(", ", $args);
		}
		$rtn .= sprintf( "#%s %s(%s): %s%s(%s)\n",
				$count,
           		!empty($frame['file']) ?  $frame['file'] :"no file",
				!empty($frame['line']) ?  $frame['line'] :"no line",
           		!empty($frame['class']) ?  $frame['class'] :"no class",
            	!empty($frame['function']) ?  $frame['function'] :"no function",
				$args );
		$count++;
	}
	return $rtn;
}



function isMobile2()
{
	if (preg_match("/(ipad)/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return false;
		}
	$arr = explode('.', $_SERVER['HTTP_HOST']);
	if($arr[0] == "m") return true;
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
		return true;
	}
	// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	if (isset($_SERVER['HTTP_VIA'])) {
		// 找不到为flase,否则为true
		return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
	}
	// 脑残法，判断手机发送的客户端标志,兼容性有待提高
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$clientkeywords = array(
				'nokia',
				'sony',
				'ericsson',
				'mot',
				'samsung',
				'htc',
				'sgh',
				'lg',
				'sharp',
				'sie-',
				'philips',
				'panasonic',
				'alcatel',
				'lenovo',
				'iphone',
				'ipod',
				'blackberry',
				'meizu',
				'android',
				'netfront',
				'symbian',
				'ucweb',
				'windowsce',
				'palm',
				'operamini',
				'operamobi',
				'openwave',
				'nexusone',
				'cldc',
				'midp',
				'wap',
				'mobile'
		);
		// 从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}
	}
	// 协议法，因为有可能不准确，放到最后判断
	if (isset($_SERVER['HTTP_ACCEPT'])) {
		// 如果只支持wml并且不支持html那一定是移动设备
		// 如果支持wml和html但是wml在html之前则是移动设备
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
			return true;
		}
	}
	return false;
}


function isMobile(){
	$r = userAgent($_SERVER['HTTP_USER_AGENT']);
	return ($r == "mobile");
}

function androidTablet($ua){ //Find out if it is a tablet
	if(strstr(strtolower($ua), 'android') ){//Search for android in user-agent
		if(!strstr(strtolower($ua), 'mobile')){ //If there is no ''mobile' in user-agent (Android have that on their phones, but not tablets)
			return true;
		}
	}
}

function userAgent($ua){
	## This credit must stay intact (Unless you have a deal with @lukasmig or frimerlukas@gmail.com
	## Made by Lukas Frimer Tholander from Made In Osted Webdesign.
	## Price will be $2

	$iphone = strstr(strtolower($ua), 'mobile'); //Search for 'mobile' in user-agent (iPhone have that)
	$android = strstr(strtolower($ua), 'android'); //Search for 'android' in user-agent
	$windowsPhone = strstr(strtolower($ua), 'phone'); //Search for 'phone' in user-agent (Windows Phone uses that)
	 
	 
	
	$androidTablet = androidTablet($ua); //Do androidTablet function
	$ipad = strstr(strtolower($ua), 'ipad'); //Search for iPad in user-agent
	 
	if($androidTablet || $ipad){ //If it's a tablet (iPad / Android)
		return 'tablet';
	}
	elseif($iphone && !$ipad || $android && !$androidTablet || $windowsPhone){ //If it's a phone and NOT a tablet
		return 'mobile';
	}
	else{ //If it's not a mobile device
		return 'desktop';
	}
}


/**
 * 字符串小助手
 *
 * @version        $Id: string.helper.php 5 14:24 2010年7月5日Z tianya $
 * @package        DedeCMS.Helpers
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
//拼音的缓冲数组
$pinyins = Array();

/**
 *  中文截取2，单字节截取模式
 *  如果是request的内容，必须使用这个函数
 *
 * @access    public
 * @param     string  $str  需要截取的字符串
 * @param     int  $slen  截取的长度
 * @param     int  $startdd  开始标记处
 * @return    string
 */
if ( ! function_exists('cn_substrR'))
{
    function cn_substrR($str, $slen, $startdd=0)
    {
        $str = cn_substr(stripslashes($str), $slen, $startdd);
        return addslashes($str);
    }
}

/**
 *  中文截取2，单字节截取模式
 *
 * @access    public
 * @param     string  $str  需要截取的字符串
 * @param     int  $slen  截取的长度
 * @param     int  $startdd  开始标记处
 * @return    string
 */
if ( ! function_exists('cn_substr'))
{
    function cn_substr($str, $slen, $startdd=0)
    {
        global $cfg_soft_lang;
        if($cfg_soft_lang=='utf-8')
        {
            return cn_substr_utf8($str, $slen, $startdd);
        }
        $restr = '';
        $c = '';
        $str_len = strlen($str);
        if($str_len < $startdd+1)
        {
            return '';
        }
        if($str_len < $startdd + $slen || $slen==0)
        {
            $slen = $str_len - $startdd;
        }
        $enddd = $startdd + $slen - 1;
        for($i=0;$i<$str_len;$i++)
        {
            if($startdd==0)
            {
                $restr .= $c;
            }
            else if($i > $startdd)
            {
                $restr .= $c;
            }

            if(ord($str[$i])>0x80)
            {
                if($str_len>$i+1)
                {
                    $c = $str[$i].$str[$i+1];
                }
                $i++;
            }
            else
            {
                $c = $str[$i];
            }

            if($i >= $enddd)
            {
                if(strlen($restr)+strlen($c)>$slen)
                {
                    break;
                }
                else
                {
                    $restr .= $c;
                    break;
                }
            }
        }
        return $restr;
    }
}

/**
 *  utf-8中文截取，单字节截取模式
 *
 * @access    public
 * @param     string  $str  需要截取的字符串
 * @param     int  $slen  截取的长度
 * @param     int  $startdd  开始标记处
 * @return    string
 */
if ( ! function_exists('cn_substr_utf8'))
{
    function cn_substr_utf8($str, $length, $start=0)
    {
        if(strlen($str) < $start+1)
        {
            return '';
        }
        preg_match_all("/./su", $str, $ar);
        $str = '';
        $tstr = '';

        //为了兼容mysql4.1以下版本,与数据库varchar一致,这里使用按字节截取
        for($i=0; isset($ar[0][$i]); $i++)
        {
            if(strlen($tstr) < $start)
            {
                $tstr .= $ar[0][$i];
            }
            else
            {
                if(strlen($str) < $length + strlen($ar[0][$i]) )
                {
                    $str .= $ar[0][$i];
                }
                else
                {
                    break;
                }
            }
        }
        return $str;
    }
}

/**
 *  HTML转换为文本
 *
 * @param    string  $str 需要转换的字符串
 * @param    string  $r   如果$r=0直接返回内容,否则需要使用反斜线引用字符串
 * @return   string
 */
if ( ! function_exists('Html2Text'))
{
    function Html2Text($str,$r=0)
    {
        if(!function_exists('SpHtml2Text'))
        {
            require_once(DEDEINC."/inc/inc_fun_funString.php");
        }
        if($r==0)
        {
            return SpHtml2Text($str);
        }
        else
        {
            $str = SpHtml2Text(stripslashes($str));
            return addslashes($str);
        }
    }
}


/**
 *  文本转HTML
 *
 * @param    string  $txt 需要转换的文本内容
 * @return   string
 */
if ( ! function_exists('Text2Html'))
{
    function Text2Html($txt)
    {
        $txt = str_replace("  ", "　", $txt);
        $txt = str_replace("<", "&lt;", $txt);
        $txt = str_replace(">", "&gt;", $txt);
        $txt = preg_replace("/[\r\n]{1,}/isU", "<br/>\r\n", $txt);
        return $txt;
    }
}

/**
 *  获取半角字符
 *
 * @param     string  $fnum  数字字符串
 * @return    string
 */
if ( ! function_exists('GetAlabNum'))
{
    function GetAlabNum($fnum)
    {
        $nums = array("０","１","２","３","４","５","６","７","８","９");
        //$fnums = "0123456789";
        $fnums = array("0","1","2","3","4","5","6","7","8","9");
        $fnum = str_replace($nums, $fnums, $fnum);
        $fnum = preg_replace("/[^0-9\.-]/", '', $fnum);
        if($fnum=='')
        {
            $fnum=0;
        }
        return $fnum;
    }
}

/**
 *  获取拼音以gbk编码为准
 *
 * @access    public
 * @param     string  $str     字符串信息
 * @param     int     $ishead  是否取头字母
 * @param     int     $isclose 是否关闭字符串资源
 * @return    string
 */
if ( ! function_exists('GetPinyin'))
{
    function GetPinyin($str, $ishead=0, $isclose=1)
    {
        global $cfg_soft_lang;
        if(!function_exists('SpGetPinyin'))
        {
            //全局函数仅是inc_fun_funAdmin.php文件中函数的一个映射
            require_once(DEDEINC."/inc/inc_fun_funAdmin.php");
        }
        if($cfg_soft_lang=='utf-8')
        {
            return SpGetPinyin(utf82gb($str), $ishead, $isclose);
        }
        else
        {
            return SpGetPinyin($str, $ishead, $isclose);
        }
    }
}
/**
 *  将实体html代码转换成标准html代码（兼容php4）
 *
 * @access    public
 * @param     string  $str     字符串信息
 * @param     long    $options  替换的字符集
 * @return    string
 */

if ( ! function_exists('htmlspecialchars_decode'))
{
        function htmlspecialchars_decode($str, $options=ENT_COMPAT) {
                $trans = get_html_translation_table(HTML_SPECIALCHARS, $options);

                $decode = ARRAY();
                foreach ($trans AS $char=>$entity) {
                        $decode[$entity] = $char;
                }

                $str = strtr($str, $decode);

                return $str;
        }
}

if ( ! function_exists('ubb'))
{
	function ubb($Text) {
		  $Text=trim($Text);
		  //$Text=htmlspecialchars($Text);
		  //$Text=ereg_replace("\n","<br>",$Text);
		  $Text=preg_replace("/\\t/is","  ",$Text);
		  $Text=preg_replace("/\[hr\]/is","<hr>",$Text);
		  $Text=preg_replace("/\[separator\]/is","<br/>",$Text);
		  $Text=preg_replace("/\[h1\](.+?)\[\/h1\]/is","<h1>\\1</h1>",$Text);
		  $Text=preg_replace("/\[h2\](.+?)\[\/h2\]/is","<h2>\\1</h2>",$Text);
		  $Text=preg_replace("/\[h3\](.+?)\[\/h3\]/is","<h3>\\1</h3>",$Text);
		  $Text=preg_replace("/\[h4\](.+?)\[\/h4\]/is","<h4>\\1</h4>",$Text);
		  $Text=preg_replace("/\[h5\](.+?)\[\/h5\]/is","<h5>\\1</h5>",$Text);
		  $Text=preg_replace("/\[h6\](.+?)\[\/h6\]/is","<h6>\\1</h6>",$Text);
		  $Text=preg_replace("/\[center\](.+?)\[\/center\]/is","<center>\\1</center>",$Text);
		  //$Text=preg_replace("/\[url=([^\[]*)\](.+?)\[\/url\]/is","<a href=\\1 target='_blank'>\\2</a>",$Text);
		  $Text=preg_replace("/\[url\](.+?)\[\/url\]/is","<a href=\"\\1\" target='_blank'>\\1</a>",$Text);
		  $Text=preg_replace("/\[url=(http:\/\/.+?)\](.+?)\[\/url\]/is","<a href='\\1' target='_blank'>\\2</a>",$Text);
		  $Text=preg_replace("/\[url=(.+?)\](.+?)\[\/url\]/is","<a href=\\1>\\2</a>",$Text);
		  $Text=preg_replace("/\[img\](.+?)\[\/img\]/is","<img src=\\1>",$Text);
		  $Text=preg_replace("/\[img\s(.+?)\](.+?)\[\/img\]/is","<img \\1 src=\\2>",$Text);
		  $Text=preg_replace("/\[color=(.+?)\](.+?)\[\/color\]/is","<font color=\\1>\\2</font>",$Text);
		  $Text=preg_replace("/\[colorTxt\](.+?)\[\/colorTxt\]/eis","color_txt('\\1')",$Text);
		  $Text=preg_replace("/\[style=(.+?)\](.+?)\[\/style\]/is","<div class='\\1'>\\2</div>",$Text);
		  $Text=preg_replace("/\[size=(.+?)\](.+?)\[\/size\]/is","<font size=\\1>\\2</font>",$Text);
		  $Text=preg_replace("/\[sup\](.+?)\[\/sup\]/is","<sup>\\1</sup>",$Text);
		  $Text=preg_replace("/\[sub\](.+?)\[\/sub\]/is","<sub>\\1</sub>",$Text);
		  $Text=preg_replace("/\[pre\](.+?)\[\/pre\]/is","<pre>\\1</pre>",$Text);
		  $Text=preg_replace("/\[emot\](.+?)\[\/emot\]/eis","emot('\\1')",$Text);
		  $Text=preg_replace("/\[email\](.+?)\[\/email\]/is","<a href='mailto:\\1'>\\1</a>",$Text);
		  $Text=preg_replace("/\[i\](.+?)\[\/i\]/is","<i>\\1</i>",$Text);
		  $Text=preg_replace("/\[u\](.+?)\[\/u\]/is","<u>\\1</u>",$Text);
		  $Text=preg_replace("/\[b\](.+?)\[\/b\]/is","<b>\\1</b>",$Text);
		  $Text=preg_replace("/\[quote\](.+?)\[\/quote\]/is","<blockquote>引用:<div style='border:1px solid silver;background:#EFFFDF;color:#393939;padding:5px' >\\1</div></blockquote>", $Text);
		  $Text=preg_replace("/\[sig\](.+?)\[\/sig\]/is","<div style='text-align: left; color: darkgreen; margin-left: 5%'><br><br>--------------------------<br>\\1<br>--------------------------</div>", $Text);
		  return $Text;
	}
}


function SpHtml2Text($str)
{
	$str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU","",$str);
	$alltext = "";
	$start = 1;
	for($i=0;$i<strlen($str);$i++)
	{
		if($start==0 && $str[$i]==">")
		{
			$start = 1;
		}
		else if($start==1)
		{
			if($str[$i]=="<")
			{
				$start = 0;
				$alltext .= " ";
			}
			else if(ord($str[$i])>31)
			{
				$alltext .= $str[$i];
			}
		}
	}
	$alltext = str_replace("　"," ",$alltext);
	$alltext = preg_replace("/&([^;&]*)(;|&)/","",$alltext);
	$alltext = preg_replace("/[ ]+/s"," ",$alltext);
	return $alltext;
}




//浏览器判断
function getBrowser(){
	$agent=$_SERVER["HTTP_USER_AGENT"];
	if(strpos($agent,'MSIE')!==false || strpos($agent,'rv:11.0')) //ie11判断
		return "ie";
	else if(strpos($agent,'Firefox')!==false)
		return "firefox";
	else if(strpos($agent,'Chrome')!==false)
		return "chrome";
	else if(strpos($agent,'Opera')!==false)
		return 'opera';
	else if((strpos($agent,'Chrome')==false)&&strpos($agent,'Safari')!==false)
		return 'safari';
	else
		return 'unknown';
}

//浏览器版本
function getBrowserVer(){
	if (empty($_SERVER['HTTP_USER_AGENT'])){    //当浏览器没有发送访问者的信息的时候
		return 'unknow';
	}
	$agent= $_SERVER['HTTP_USER_AGENT'];
	if (preg_match('/MSIE\s(\d+)\..*/i', $agent, $regs))
		return $regs[1];
	elseif (preg_match('/FireFox\/(\d+)\..*/i', $agent, $regs))
	return $regs[1];
	elseif (preg_match('/Opera[\s|\/](\d+)\..*/i', $agent, $regs))
	return $regs[1];
	elseif (preg_match('/Chrome\/(\d+)\..*/i', $agent, $regs))
	return $regs[1];
	elseif ((strpos($agent,'Chrome')==false)&&preg_match('/Safari\/(\d+)\..*$/i', $agent, $regs))
	return $regs[1];
	else
		return 'unknow';
}



/**
 *  文档自动分页
 *
 * @access    public
 * @param     string  $mybody  内容
 * @param     string  $spsize  分页大小
 * @param     string  $sptag  分页标记
 * @return    string
 */
function SpLongBody($mybody, $spsize, $sptag)
{
	if(strlen($mybody) < $spsize)
	{
		return $mybody;
	}
	$mybody = stripslashes($mybody);
	$bds = explode('<', $mybody);
	$npageBody = '';
	$istable = 0;
	$mybody = '';
	foreach($bds as $i=>$k)
	{
		if($i==0)
		{
			$npageBody .= $bds[$i]; continue;
		}
		$bds[$i] = "<".$bds[$i];
		if(strlen($bds[$i])>6)
		{
			$tname = substr($bds[$i],1,5);
			if(strtolower($tname)=='table')
			{
				$istable++;
			}
			else if(strtolower($tname)=='/tabl')
			{
				$istable--;
			}
			if($istable>0)
			{
				$npageBody .= $bds[$i]; continue;
			}
			else
			{
				$npageBody .= $bds[$i];
			}
		}
		else
		{
			$npageBody .= $bds[$i];
		}
		if(strlen($npageBody)>$spsize)
		{
			$mybody .= $npageBody.$sptag;
			$npageBody = '';
		}
	}
	if($npageBody!='')
	{
		$mybody .= $npageBody;
	}
	return addslashes($mybody);
}

function ShowLongBody($mybody, $spsize, $sptag)
{
	$bodylen = strlen($mybody);
	$reckonPageNum = $bodylen / $spsize; //预估能分多少而，如果第一次explode页数小于此页，则进行下一个explode
	$reckonPageNum  = 2;
	if($bodylen < $spsize)
	{
		return array($mybody);
	}

	$arr = splitBody($mybody,'',$reckonPageNum);

	/*	$spliter = '</p>';
	 $arr = explode($spliter,$mybody);

	 if(count($arr) < $reckonPageNum){
		$spliter = '<br />';
		$arr = explode($spliter,$mybody);
		if(count($arr) < $reckonPageNum){
		$spliter = '<br>';
		$arr = explode($spliter,$mybody);
		}
		}
	*/
	$temp = '';
	$c = count($arr);
	for($i = 0; $i < $c; $i+=2){
		if($i != $c-1)
			$temp .= $arr[$i].$arr[$i+1];
		//var_dump($temp);
		//var_dump($arr[)
		if(strlen($temp) >= $spsize){
			$pageContent[] = $temp;
			$temp = '';
				
		}
	}
	if(!empty($temp)){ //末尾不足分页大小的部分
		$pageContent[] = $temp;
	}
	//print_r($pageContent);exit;
	return $pageContent;
}

function splitBody($str,$spliter,$reckonPageNum){
	$r = preg_split('/(<\/p>|<br>|<br \/>|<\/div>)/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
	return $r;

	print_r($r);exit('---------');
	//先查找分隔符数量，然后选取最接近预估数的作为分隔符 ,如果每个分隔块与分块大小之差大于50%,则需两次分割
	$spliters = array('</p>', '<br>','<br />', '</div>');
	$spliter_nums = array();
	foreach($spliters as $v){
		//echo $v;
		$spliter_nums[] = substr_count($str,$v);
	}
	var_dump($spliter_nums);exit;

	$spliter = '</p>';
	$pnum = substr_count($str,$spliter);

	$spliter = '<br />';
	$bnum = substr_count($str,$spliter);

	$spliter = '<br>';
	$b2num = substr_count($str,$spliter);

	$spliter = '</div>';
	$dnum = substr_count($str,$spliter);

	max(array($pnum,$b2num,$bnum,$dnum));

}


/**
 *  文档自动分页
 *
 * @access    public
 * @param     string  $mybody  内容
 * @param     string  $spsize  分页大小
 * @param     string  $sptag  分页标记
 * @return    string
 */
function ShowLongBodyxxxxx($mybody, $spsize, $sptag)
{
	if(strlen($mybody) < $spsize)
	{
		return $mybody;
	}
	$mybody = stripslashes($mybody);
	$bds = explode('</', $mybody);
	$npageBody = '';
	$istable = 0;
	$mybody = '';
	$pageContent = array();
	foreach($bds as $i=>$k)
	{
		if($i==0)
		{
			$npageBody .= $bds[$i]; continue;
		}
		$bds[$i] = "</".$bds[$i];
		if(strlen($bds[$i])>6)
		{
			$tname = substr($bds[$i],1,5);
			if(strtolower($tname)=='table')
			{
				$istable++;
			}
			else if(strtolower($tname)=='/tabl')
			{
				$istable--;
			}
			if($istable>0)
			{
				$npageBody .= $bds[$i]; continue;
			}
			else
			{
				$npageBody .= $bds[$i];
			}
		}
		else
		{
			$npageBody .= $bds[$i];
		}
		if(strlen($npageBody)>$spsize)
		{
			$pageContent[] = $npageBody.$sptag;
			$npageBody = '';
		}
	}
	if($npageBody!='')
	{
		$mybody .= $npageBody;
	}

	print_r($pageContent);
	return $pageContent;
	return addslashes($mybody);
}

/**
 * 从数组中取指定字段
 * @param array $data 数据
 * @param array $field  字段数组
 * @return multitype:array
 */
function field($data,$field){
	$ret = array();
	foreach ($field as $v){
		$ret[$v] = $data[$v];
	}
	return $ret;
}

function member_thumb(&$r){
	$defaultImg = $r['sex'] ? C('IMG_SEX_1') : C('IMG_SEX_0');
	$r['thumb'] = empty($r['thumb']) ? $defaultImg :URL_IMG.'/'.$r['thumb'];
}

function thumb_privew($thumb){
	return empty($thumb) ? C('DEFAULT_AVATAR') : URL_IMG.'/'.$thumb;
}

//阿拉伯数字转中文数字
function ToChinaseNum($num)
{
    $char = array("零","一","二","三","四","五","六","七","八","九");
    $dw = array("","十","百","千","万","亿","兆");
    $retval = "";
    $proZero = false;
    for($i = 0;$i < strlen($num);$i++)
    {
        if($i > 0)    $temp = (int)(($num % pow (10,$i+1)) / pow (10,$i));
        else $temp = (int)($num % pow (10,1));

        if($proZero == true && $temp == 0) continue;

        if($temp == 0) $proZero = true;
        else $proZero = false;

        if($proZero)
        {
            if($retval == "") continue;
            $retval = $char[$temp].$retval;
        }
        else $retval = $char[$temp].$dw[$i].$retval;
    }
    if($retval == "一十") $retval = "十";
    return $retval;
}


/**
 * curl
 *
 * @param
 *        	string url
 * @param
 *        	array 数据
 * @param
 *        	int 请求超时时间
 * @param
 *        	bool HTTPS时是否进行严格认证
 * @return string
 */
function curl_get_content($url, $data = "", $method = "get", $timeout = 30, $CA = false){

    // $url = "http://www.baidu.com";
    $cacert = getcwd() . '/cacert.pem'; // CA根证书
    $SSL = substr($url, 0, 8) == "https://" ? true : false;
    $ch = curl_init();
    if(is_object($data)){
        $data = (array)$data;
    }


    $method = strtolower($method);
    if($method == 'get') {
        if(is_array($data)) {
            $data = http_build_query($data);
        }
        $url .= "?" . $data;
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //data with URLEncode
    }
    //echo $url;
    //var_dump($data);exit;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 2);
    if($SSL && $CA) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 只信任CA颁布的证书
        curl_setopt($ch, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
    } else if($SSL && ! $CA) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Expect:'
    )); // 避免data数据过长问题
    // var_dump($data);

    $headerArr[] = 'PARAMS:android#1.4.2#wandoujias';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
    //curl_setopt($ch, CURLOPT_PROXY, "192.168.22.211:8888");
    $ret = curl_exec($ch);
    if(empty($ret)) {
        var_dump(curl_error($ch)); // 查看报错信息
    }
    // var_dump($ret);
    // exit('x');
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($httpCode != 200) {
        $tmp = array();
        $tmp['http_code'] = $httpCode;
        $tmp['data'] = $ret;

        //echo "\n服务器错误：$httpCode";
        //echo $ret;
        $ret = json_encode($tmp);
    }
    curl_close($ch);
    //var_dump($ret);
    return $ret;
}


function curl_get_content2($url, $data = "", $method = "get",$header = [], $timeout = 30, $CA = false){

    // $url = "http://www.baidu.com";
    $cacert = getcwd() . '/cacert.pem'; // CA根证书
    $SSL = substr($url, 0, 8) == "https://" ? true : false;
    $ch = curl_init();
    if(is_object($data)){
        $data = (array)$data;
    }


    $method = strtolower($method);
    if($method == 'get') {
        if(is_array($data)) {
            $data = http_build_query($data);
        }
        $url .= "?" . $data;
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //data with URLEncode
    }
    //echo $url;
    //var_dump($data);exit;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 2);
    if($SSL && $CA) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 只信任CA颁布的证书
        curl_setopt($ch, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
    } else if($SSL && ! $CA) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Expect:'
    )); // 避免data数据过长问题
    // var_dump($data);

    $header[] = 'PARAMS:android#1.4.2#wandoujias';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    //curl_setopt($ch, CURLOPT_PROXY, "192.168.22.211:8888");
    $ret = curl_exec($ch);
    if(empty($ret)) {
        var_dump(curl_error($ch)); // 查看报错信息
    }
    // var_dump($ret);
    // exit('x');
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($httpCode != 200) {
        $tmp = array();
        $tmp['http_code'] = $httpCode;
        $tmp['data'] = $ret;

        //echo "\n服务器错误：$httpCode";
        //echo $ret;
        $ret = json_encode($tmp);
    }
    curl_close($ch);
    //var_dump($ret);
    return $ret;
}

function curl_post($url,$data,$header,$post=1)
{
    //初始化curl
    $ch = curl_init();
    //参数设置
    $res= curl_setopt ($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt ($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, $post);
    if($post)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        if(CONF_ENV == "dev"){
            //curl_setopt($ch, CURLOPT_PROXY, "192.168.3.173:8888");
        }
       // var_dump($data);exit;
        //exit('x');
        $result = curl_exec ($ch);
        //连接失败
        if($result == FALSE){
             $result = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><Response><statusCode>172001</statusCode><statusMsg>网络错误</statusMsg></Response>";
        }

        curl_close($ch);
        return $result;
}

//短信发送
function send_sms($mobile,$msg){
    return ronglian($mobile, $msg);
}

function ronglian($mobile,$msg){
    $tempId = "152677";
    $accountSid= '8a216da854ebfcf70154feea366d0d36';
    //主帐号Token
    $accountToken= 'f035bd2b42b94b30bab5442c51de6a90';
    //应用Id
    $appId='8aaf070859203efa015929c175bd05e6';
    $serverIP='app.cloopen.com';
    $serverPort='8883';
    $batch = date("YmdHis");
    $softVersion='2013-12-26';
    $msg2 = "10分钟";
    $data = "<TemplateSMS>
    <to>{$mobile}</to>
    <appId>{$appId}</appId>
    <templateId>{$tempId}</templateId>
    <datas><data>{$msg}</data><data>{$msg2}</data></datas>
    </TemplateSMS>";
   // $url ="https://app.cloopen.com:8883/2013-12-26/Accounts/8a216da854ebfcf70154feea366d0d36/SMS/TemplateSMS?sig=F04346BB3BAEEC750A8BC82C0F4FBCD8";
 
    //主帐号
    
    // 大写的sig参数
    $sig =  strtoupper(md5($accountSid . $accountToken . $batch));
    $url="https://$serverIP:$serverPort/$softVersion/Accounts/$accountSid/SMS/TemplateSMS?sig=$sig";
    // 生成授权：主帐户Id + 英文冒号 + 时间戳。
    $authen = base64_encode($accountSid . ":" . $batch);
    // 生成包头
    $header = array("Accept:application/xml","Content-Type:application/xml;charset=utf-8","Authorization:$authen");
    // 发送请求
    $result = curl_post($url,$data,$header);
    $result = simplexml_load_string(trim($result," \t\n\r"));
    if($result->statusCode!=0) {
         //echo "error code :" . $result->statusCode . "<br>";
         //echo "error msg :" . $result->statusMsg . "<br>";
         $result = (array)$result;
         return ["code" => $result['statusCode'],"msg" => $result['statusMsg']];
         //return false;
     }else{
         return true;
         //echo "Sendind TemplateSMS success!<br/>";
         // 获取返回信息
         //$smsmessage = $result->TemplateSMS;
         //echo "dateCreated:".$smsmessage->dateCreated."<br/>";
         //echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
         //TODO 添加成功处理逻辑
     }
    //curl_post($url, $data, $header);
}

//16进行车32进行，功能类似于短链接生成
function num16to32($md5){
    for($a = md5( $md5, true ), $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV', $d = '', $f = 0;
    $f < 8;
    $g = ord( $a[ $f ] ), $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],$f++
    );
    return $d;
}

//编码订单号,3位随机数+订单号+5们随机数
function order_encode($id){
    return mt_rand(100,999).$id.mt_rand(10000,99999);
}

//解码订单号
function order_decode($id){
    return substr($id,3,-5);
}


if (!function_exists('getallheaders')){
    function getallheaders($raw=false) { 
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
            	$key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        if($raw){
            $str = "";
            foreach ($headers as $k => $v){
                $str .= "$k: $v\r\n";
            }
            return $str;
        }
        return $headers; 
    }
}

function make_coupon_card() {
    $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $rand = $code[rand(0,25)]
        .strtoupper(dechex(date('m')))
        .date('d').substr(time(),-5)
        .substr(microtime(),2,5)
        .sprintf('%02d',rand(0,99));
    for(
        $a = md5( $rand, true ),
        $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
        $d = '',
        $f = 0;
        $f < 8;
        $g = ord( $a[ $f ] ),
        $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
        $f++
    );
    return $d;
}
//echo make_coupon_card();


/**
 * 十进制数转换成62进制
 *
 * @param integer $num
 * @return string
 */
function to62($num) {
    $to = 62;
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $ret = '';
    do {
        $ret = $dict[bcmod($num, $to)] . $ret;
        $num = bcdiv($num, $to);
    } while ($num > 0);
    return $ret;
}

/**
 * 62进制数转换成十进制数
 *
 * @param string $num
 * @return string
 */
function from62($num) {
    $from = 62;
    $num = strval($num);
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($num);
    $dec = 0;
    for($i = 0; $i < $len; $i++) {
        $pos = strpos($dict, $num[$i]);
        $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
    }
    return $dec;
}

function datetime(){
	return date('Y-m-d H:i:s',time());
}

//字符串version转int
function versionToInt($version){
	if(strpos($version,',') === false){
		return $version;
	}else{
        $decimals = explode('.',$version)[1];
        return $version*$decimals;
	}

}
