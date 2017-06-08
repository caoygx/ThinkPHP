<?php
namespace Common;
use Think\Controller;
use Org\Util\Rbac;
use Org\Util\Cookie;
use Common\XPage;
class CommonController extends Controller {
	protected $m = null;
	protected $user = array(); //用户信息数组
	protected $uid = 0; //用户uid
	protected $u = null;
	protected $autoInstantiateModel = true;
	protected $tempStorageOpenidUser = []; //微信等第三方登录的用户信息临时存储
	protected $isMobile;
	public function __construct($pre = ''){
		parent::__construct();
		if($pre){ //有表前缀
			new CommonModel(CONTROLLER_NAME,$pre);
		}else{
			try{
                //检测表存在，则实例化
				$model = M();
                $tablename = strtolower(C('DB_PREFIX').parse_name(CONTROLLER_NAME));
				$sqlCheckTable = "SELECT * FROM information_schema.tables WHERE table_name = '$tablename'";
                $tableExist = $model->query($sqlCheckTable);
                debug($tableExist);
				if($this->autoInstantiateModel && !empty($tableExist)){
					//if(class_exists(CONTROLLER_NAME.'Model',true)){
						$this->m = D(CONTROLLER_NAME); //实例化model
					//}
					if(empty($this->m)){
						$this->m = M(CONTROLLER_NAME);
					}
				}
			}catch(Exception $e){
				//echo $e->getMessage();
				//
			}
		}
		
		
		//简单的权限验证操作
		if (method_exists ( $this, '_permissionAuthentication' )) {
			$this->_permissionAuthentication ();
		}

		//if(empty($this->m)) exit(CONTROLLER_NAME.'对象不存在');

	}
	
	
	function _initialize() {
		if(!IS_CLI)	$this->requestLog();

	}


	public function index() {


		//列表过滤器，生成查询Map对象
		$map = $this->_search ();
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}


		$name=CONTROLLER_NAME;
		//$model = D ($name);
		if (! empty ( $this->m )) {
			$this->_list ( $this->m, $map );
		}
		$this->toview ();
		return;
	}

	public function msg($result,$text = '',$url=''){
		if(false !== $result){
			$this->success($text."成功");
		}else{
			$this->error($text."失败");
		}
	}
	
	//获取用户登录凭证信息
	function getAuth(){
		$u = getUserAuth();
		if(CONF_ENV=='dev'){
			//$u['uid'] = 4;
		}
		if(empty($u) && !empty($this->tempStorageOpenidUser)) $u = $this->tempStorageOpenidUser;
		$this->user = $u;
		$this->uid = $this->user['uid'];
		return $u;
	}
	
	
	public $logId;
	/**
	 * 访问日志，记录用户请求的参数
	 */
	function requestLog(){


		$data = array();
		$data['url'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		if(IS_POST){
			$params = $_POST;
		}elseif(IS_GET){
			$params = $_GET;
		}
		if(empty($params)) $params['input'] = file_get_contents("php://input");
		$data['params'] = json_encode($params);
		//$data['cookie'] = json_encode($_COOKIE);
		//$data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$data['ip'] = get_client_ip();
		$detail = array();
		$detail['request'] = $_REQUEST;
		
		$header = [];
		$fields = ['HTTP_USER_ID','HTTP_DEVICE_VID','HTTP_DEVICE_ID','HTTP_PLATFORM','HTTP_VERSION']; //'HTTP_USER_AGENT',
		foreach ($fields as $k => $v){
			if(empty($_SERVER[$v])) continue;
		    $header[$v] = $_SERVER[$v];
		}
		/*$this->version = I('server.HTTP_VERSION');
		$this->device_id = I('device_id') ?:I('server.HTTP_DEVICE_ID');
		$this->platform = I('server.HTTP_PLATFORM');
		$user_id = I('user_id') ?: I('server.HTTP_USER_ID');
		$detail['server'] = $_SERVER;*/
        //$detail['header'] = $header;
        //$data['detail'] = json_encode($detail);
		$url = $_SERVER['REQUEST_METHOD']." ".$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']." ".$_SERVER['SERVER_PROTOCOL']."\r\n";
        $request = $url.getallheaders(true);

        $raw_post = '';
        if(IS_POST){
            $raw_post = http_build_query($_POST);
            if(empty($raw_post)){
                $raw_post = file_get_contents("php://input");
			}
        }
        $request .= "\r\n".$raw_post;

        $data['detail'] = $request;
        $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $data['platform'] = I('server.HTTP_PLATFORM');
        $data['user_id'] = I('server.HTTP_USER_ID');
		$data['create_time'] = date("Y-m-d H:i:s");
		$data['method'] = $_SERVER['REQUEST_METHOD'];
		$m = M('LogRequest','','log');
		//$m->create($data);
		$this->logId = $m->add($data);
		//echo $m->getLastSql();exit;
	
	}
	
	function responseLog($id,$response){
	    $data = [];
	    $data['id'] = $id;
	    $data['response'] = $response;
	   // $m =  M('LogRequest','','log');
	   // $m->save($data);
	    
	}
	
	public function lists() {


	    //列表过滤器，生成查询Map对象
	    $map = $this->_search ();
	    if (method_exists ( $this, '_filter' )) {
	        $this->_filter ( $map );
	    }
	    $name=CONTROLLER_NAME;
	    //$model = D ($name);
	    if (! empty ( $this->m )) {
	        $this->_list2 ( $this->m, $map );
	    }
	    exit('lists erorr');
	   // $this->display ();
	    //return;
	}

	//有连接表显示列表
	public function indexLink($option=array()) {
		//列表过滤器，生成查询Map对象
		$map = $this->_search ();
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$name=CONTROLLER_NAME;
		//$model = D ($name);
		if (! empty ( $this->m )) {
			if($option['join']){
				$this->_listLink ( $this->m, $map,$option );
			}else{
				$this->_list($this->m,$map);
			}
		}
		$this->display ();
		return;
	}

	/**
     +----------------------------------------------------------
	 * 取得操作成功后要返回的URL地址
	 * 默认返回当前模块的默认操作
	 * 可以在action控制器中重载
     +----------------------------------------------------------
	 * @access public
     +----------------------------------------------------------
	 * @return string
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	function getReturnUrl() {
		return __CONTROLLER__ . '/'  .   C ( 'DEFAULT_ACTION' );
	}

	/**
     +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
     +----------------------------------------------------------
	 * @access protected
     +----------------------------------------------------------
	 * @param string $name 数据对象名称
     +----------------------------------------------------------
	 * @return HashMap
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	protected function _search($name = '') {
		//生成查询条件
		if (empty ( $name )) {
			$name = CONTROLLER_NAME;
		}
		//$name=CONTROLLER_NAME;
		//$model = D ( $name );
		//var_dump($this->m);exit;
		$map = array ();
		foreach ( $this->m->getDbFields () as $key => $val ) {
			if (isset ( $_REQUEST [$val] ) && $_REQUEST [$val] != '') {
				$map [$val] = trim($_REQUEST [$val]);
			}
		}
		return $map;

	}

	/**
     +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
     +----------------------------------------------------------
	 * @access protected
     +----------------------------------------------------------
	 * @param Model $model 数据对象
	 * @param HashMap $map 过滤条件
	 * @param string $sortBy 排序
	 * @param boolean $asc 是否正序
     +----------------------------------------------------------
	 * @return void
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	protected function _list($model, $map, $sortBy = '', $asc = false) {
		//排序字段 默认为主键名
		if (isset ( $_REQUEST ['_order'] )) {
			$order = $_REQUEST ['_order'];
		} else {
			$order = ! empty ( $sortBy ) ? $sortBy : $model->getPk ();
		}
		//排序方式默认按照倒序排列
		//接受 sost参数 0 表示倒序 非0都 表示正序
		//$setOrder = setOrder(array(array('viewCount', 'a.view_count'), 'a.id'), $orderBy, $orderType, 'a');
		if (isset ( $_REQUEST ['_sort'] )) {
			$sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
		} else {
			$sort = $asc ? 'asc' : 'desc';
		}
		//取得满足条件的记录数
		$pk = $model->getPk();
		$count = $model->where ( $map )->count ( $pk );//echo $model->getlastsql();exit('count');
		if ($count > 0) {
			import ( "ORG.Util.Page" );
			//创建分页对象
			if (! empty ( $_REQUEST ['listRows'] )) {
				$listRows = $_REQUEST ['listRows'];
			} else {
				$listRows = '10';
			}
			$p = new \Think\Page ( $count, $listRows );
			$p->rollPage = 7;
			//echo C('PAGE_STYLE');exit;
			$p->style = C('PAGE_STYLE');//设置风格
			//分页查询数据
			//var_dump($p->listRows);exit;
			$voList = $model->where($map)->order( "`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->select ( );
			if (method_exists ( $this, '_join' )) {
				$this->_join ( $voList );
			}
			//var_dump($voList);exit;
			//echo $model->getlastsql();exit('x');
			//分页跳转的时候保证查询条件
			foreach ( $map as $key => $val ) {
				if (! is_array ( $val )) {
					$p->parameter .= "$key=" . urlencode ( $val ) . "&";
				}
			}
			//分页显示
			$page = $p->show ();
			//列表排序显示
			$sortImg = $sort; //排序图标
			$sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
			$sort = $sort == 'desc' ? 1 : 0; //排序方式
			//模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'sort', $sort );
			$this->assign ( 'order', $order );
			$this->assign ( 'sortImg', $sortImg );
			$this->assign ( 'sortType', $sortAlt );
			$this->assign ( "page", $page );
		}
		cookie( '_currentUrl_', __SELF__ );
		return;
	}

	/**
	 +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param Model $model 数据对象
	 * @param HashMap $map 过滤条件
	 * @param string $sortBy 排序
	 * @param boolean $asc 是否正序
	 +----------------------------------------------------------
	 * @return void
	 +----------------------------------------------------------
	 * @throws ThinkExecption
	 +----------------------------------------------------------
	 */
	protected function _list2($model, $map, $sortBy = '', $asc = false) {
	    //排序字段 默认为主键名
	    if (isset ( $_REQUEST ['_order'] )) {
	        $order = $_REQUEST ['_order'];
	    } else {
	        $order = ! empty ( $sortBy ) ? $sortBy : $model->getPk ();
	    }
	    //排序方式默认按照倒序排列
	    //接受 sost参数 0 表示倒序 非0都 表示正序
	    //$setOrder = setOrder(array(array('viewCount', 'a.view_count'), 'a.id'), $orderBy, $orderType, 'a');
	    if (isset ( $_REQUEST ['_sort'] )) {
	        $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
	    } else {
	        $sort = $asc ? 'asc' : 'desc';
	    }
	    //取得满足条件的记录数
	    $pk = $model->getPk();
	    $count = $model->where ( $map )->count ( $pk );
	    //echo $model->getlastsql();exit('count');
	    $ret = array();
	    if ($count > 0) {
	        import ( "ORG.Util.Page" );
	        //创建分页对象
	        if (! empty ( $_REQUEST ['listRows'] )) {
	            $listRows = $_REQUEST ['listRows'];
	        } else {
	            $listRows = 10;
	        }
	        $p = new \Think\PageJs ( $count, $listRows );
	        //echo C('PAGE_STYLE');exit;
	        $p->style = C('PAGE_STYLE');//设置风格
	        //分页查询数据
	        //var_dump($p);exit;
	        $voList = $model->where($map)->order( "`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->select ( );
	        if (method_exists ( $this, '_join' )) {
	            $this->_join ( $voList );
	        }
	        //var_dump($voList);exit;
	        //echo $model->getlastsql();exit('x');
	        //分页跳转的时候保证查询条件
	        foreach ( $map as $key => $val ) {
	            if (! is_array ( $val )) {
	                /*$p->parameter = http_build_query($p->parameter);
	            	$p->parameter .= "$key=" . urlencode ( $val ) . "&";*/
	            }
	        }
	        //分页显示
	        $page = $p->show ();
	        //列表排序显示
	        $sortImg = $sort; //排序图标
	        $sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
	        $sort = $sort == 'desc' ? 1 : 0; //排序方式

	        $ret['list'] = $voList;
	       
	        /* if($isweb){
    	        $ret['sort'] = $sort;
    	        $ret['order'] = $order;
    	        $ret["page"] = $page ;
	        } */
	        //模板赋值显示
	       /*  $this->assign ( 'list', $voList );
	        $this->assign ( 'sort', $sort );
	        $this->assign ( 'order', $order );
	        $this->assign ( 'sortImg', $sortImg );
	        $this->assign ( 'sortType', $sortAlt );*/

	    }
	   // echo $model->getLastSql();
	   $this->success($ret);
	}





	public function show($content="",$charset='',$contentType='',$prefix=''){
		$id = I('id');
		$vo = $this->m->getById ( $id );
		if (method_exists ( $this, '_show' )) {
			$this->_show ( $vo );
		}
		$this->vo = $vo;
		$this->toview();

    }




	function responseFormat(){
	    $format = "";
	    if(IS_AJAX || C('RETRUN_FORMAT') == "android_json" || I('ret_format') == 'json' || $_SERVER['HTTP_ACCEPT'] == 'application/json'){ //json,app: code,msg,data
	        return "json";
	    }elseif (!empty(I(C('VAR_JSONP_HANDLER')))){ //jsonp
	        return "jsonp";
	    }elseif(isMobile()){ 
	        return "wap";
	    }else{
	        return "web";
	    }
	}

	/**
	* @name 根据请求方式，显示对应的格式到页面
    * @param  数据  array $data
	* @param  格式类型  int $type
    * @return   member
    */
	public function toview($data = "",  $tpl=""){
		if(empty($data)) $data = $this->get();
		//if(!empty($tpl)) $this->display($tpl);
		//var_dump($_SERVER);exit;
		if(IS_AJAX || C('RETRUN_FORMAT') == "android_json" || I('ret_format') == 'json' || $_SERVER['HTTP_ACCEPT'] == 'application/json'){ //json,app: code,msg,data
			if(empty($data)) $data = (object)$data;
		    $this->success($data,"",1);
		}elseif (!empty(I(C('VAR_JSONP_HANDLER')))){ //jsonp 
			$this->ajaxReturn(array("code" =>1, "msg" => "","data" => $data),'JSONP');
		}elseif(isMobile()){ //wap
			$wapTpl = "wap".ACTION_NAME;
			$templateFile   =   $this->view->parseTemplate($wapTpl);
			
			//var_dump($templateFile);exit;
			//if()
			if("http://".$_SERVER['HTTP_HOST'] !=URL_M && "http://".$_SERVER['HTTP_HOST'] != URL_USER) redirect(URL_M.__SELF__);
			if(is_file($templateFile)) $this->display($wapTpl);
			else $this->display($tpl);
		}else{ //web
    		$this->display($tpl);
		}

	}

	function success($message='',$jumpUrl='',$ajax=false){
		$this->dispatchJump2($message,1,$jumpUrl,$ajax);
	}
	function error($message='',$jumpUrl='',$ajax=false){
		$status = 0;
		$this->responseFormat() && $ajax = 1;
		if($ajax || IS_AJAX) {// AJAX提交
			$data           =   is_array($ajax)?$ajax:array();
			$data['code'] =   $status;
			$data['msg']   =   $message;
			$data['data']    =   (object)array();
			$this->ajaxReturn($data);
		}
		if(is_int($ajax)) $this->assign('waitSecond',$ajax);
		if(!empty($jumpUrl)) $this->assign('jumpUrl',$jumpUrl);
		// 提示标题
		$this->assign('msgTitle',$status? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
		//如果设置了关闭窗口，则提示完毕后自动关闭窗口
		if($this->get('closeWin'))    $this->assign('jumpUrl','javascript:window.close();');
		$this->assign('status',$status);   // 状态
		//保证输出不受静态缓存影响
		C('HTML_CACHE_ON',false);
		if($status) { //发送成功信息
			$this->assign('message',$message);// 提示信息
			// 成功操作后默认停留1秒
			if(!isset($this->waitSecond))    $this->assign('waitSecond','1');
			// 默认操作成功自动返回操作前页面
			if(!isset($this->jumpUrl)) $this->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
			$this->display(C('TMPL_ACTION_SUCCESS'));
		}else{
			$this->assign('error',$message);// 提示信息
			//发生错误时候默认停留3秒
			if(!isset($this->waitSecond))    $this->assign('waitSecond','3');
			// 默认发生错误的话自动返回上页
			if(!isset($this->jumpUrl)) $this->assign('jumpUrl',"javascript:history.back(-1);");
			$this->display(C('TMPL_ACTION_ERROR'));
			// 中止执行  避免出错后继续执行
			exit ;
		}
	}
	
	function dispatchJump2($message='',$status = 1,$jumpUrl='',$ajax=false){
		if($ajax || IS_AJAX) {// AJAX提交
			$data           =   is_array($ajax)?$ajax:array();
			$data['code'] =   $status;
			$data['msg']    =   "";
			$data['data']   =   $message;
			
			$this->ajaxReturn($data);
		}
		if(is_int($ajax)) $this->assign('waitSecond',$ajax);
		if(!empty($jumpUrl)) $this->assign('jumpUrl',$jumpUrl);
		// 提示标题
		$this->assign('msgTitle',$status? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
		//如果设置了关闭窗口，则提示完毕后自动关闭窗口
		if($this->get('closeWin'))    $this->assign('jumpUrl','javascript:window.close();');
		$this->assign('status',$status);   // 状态
		//保证输出不受静态缓存影响
		C('HTML_CACHE_ON',false);
		if($status) { //发送成功信息
			$this->assign('message',$message);// 提示信息
			// 成功操作后默认停留1秒
			if(!isset($this->waitSecond))    $this->assign('waitSecond','1');
			// 默认操作成功自动返回操作前页面
			if(!isset($this->jumpUrl)) $this->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
			$this->display(C('TMPL_ACTION_SUCCESS'));
		}else{
			$this->assign('error',$message);// 提示信息
			//发生错误时候默认停留3秒
			if(!isset($this->waitSecond))    $this->assign('waitSecond','3');
			// 默认发生错误的话自动返回上页
			if(!isset($this->jumpUrl)) $this->assign('jumpUrl',"javascript:history.back(-1);");
			$this->display(C('TMPL_ACTION_ERROR'));
			// 中止执行  避免出错后继续执行
			exit ;
		}
	}

	//用户信息
	function userinfo(){
		if(empty($this->uid)) return;

		$u = M('User');
		$userinfo = $u->find($this->uid);
		unset($userinfo['id']);
		unset($userinfo['pwd']);
		unset($userinfo['open_id']);
		unset($userinfo['bind']);
		$userinfo = json_encode($userinfo);
		$this->userinfo = $userinfo;

	}

	//右边栏
	function right(){
		$f = M('Family');
		$r = $f->where("status = 1")->order("num desc")->limit(5)->select();
		$this->listByNum = $r;

	}

	//设置标题
	function setTitle($title){
		$this->pageTitle = empty($title) ? C('SITE_TITLE') :  $title.'_'.C('SITE_TITLE');
		//$title && $title = $title."_";
		//$this->pageTitle = $title.C('SITE_TITLE');
	}


}
?>
