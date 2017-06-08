<?php
namespace Common\Model;
class UserModel extends CommonModel {
	public $_auto		=	array(
		//array('create_time','time',self::MODEL_INSERT,'function'),
	    //array('password','pwd',3,'callback'),
	   // array('salt','autoSalt',1,'callback'),
	    array('ip','get_client_ip',1,'function'),
	);
	
    protected $_validate = array(
        array('device_id','require','设备id是必须的'), //默认情况下用正则进行验证
       // array('username','','帐号名称已经存在！',1,'unique',1), // 在新增的时候验证name字段是否唯一
        //array('username','/^[\x{4e00}-\x{9fa5}A-Za-z0-9_\-@\.]{1,16}$/u','帐号名称格式不正确！',1,'regex',1), // 验证账号格式，中文+字母+@.-
        //array('password',1,'密码格式不正确',1,'length'), // 自定义函数验证密码格式
        //array('repassword','password','确认密码不正确',1,'confirm'), // 验证确认密码是否和密码一致
    );
 
	
	function msave($data = array()){
		if(false === $this->create($data))  return false;
	    return $this->add();
	}
	
	//自动 完成密码加密
	protected function pwd($password){
	    return password_hash($password, PASSWORD_DEFAULT);
	}
	
	//自动生成salt
	protected function autoSalt($password){
	     return substr(uniqid(mt_rand()), 0, 4);
	}
	
	//第三方注册，已注册则返回userid
	function oauthAdd($open_id,$type){
		$where = [];
		$where['open_id'] = $open_id;
		if(!empty($type))	$where['type'] = $type; 
		$r = $this->where ($where)->find();
		if(!empty($r)) return false;
		$data ['open_id'] = $open_id;
		
		//获取微信用户信息，入库
		switch ($type) {
			case 2:
				$data['nickname'] = "QQ用户";;
				break;
			
			case 3:
				$data['nickname'] = "微信用户";;
			break;
			
			case 4:
				$data['nickname'] = "微博用户";;
				break;
			
			default:;
				break;
		}
	 
		//$data ['nickname'] = $userInfo ['nickname'];
		//$data ['avatar'] = $userInfo ['figureurl_2'];
		$data ['ip'] = get_client_ip ();
		$data['create_time'] = time();
		$data['lastlogin_time'] = time();
		$data['lastlogin_ip'] = get_client_ip ();
		$data['type'] = $type;
		$this->add ( $data );
		//session ( 'id', $this->getLastInsID () );
		//$u['uid'] = $this->getLastInsID();

	}
	
	function existOpenId($open_id){
		$r = $this->getByOpen_id($open_id);
	}
	
	
	/**
	 * 得到一条记录
	 * @param unknown_type $id 
	 */
	public function getOne($id){
			if(!is_numeric($id)) return;
			$r = $this->find($id);
			$r['user_id'] = $r['id'];
			return $r;
	}
	
	/**
	 * 通过uid得到一条记录
	 * @param unknown_type $uid
	 */
	public function getOneByUid($uid){
		$r = $this->getList(array("uid"=>$uid));
		$this->standardizeData($r);
		//dump($r);
		return $r;
	}
	
	/**
	 * 标准化数据
	 * @param unknown_type $r
	 */
	protected function standardizeData(&$r){
		foreach($r as $k => $v){
			if(empty($v['nickname'])){
				$r[$k]['nickname'] = $v['uname'];
			}
		}
	}
	
	
	//登录成功后处理
	function loginSuccess($u){
	    $u['uid'] = $u['id'];
	    setUserAuth($u);
	    
	    //记录登录信息
	    $ip		=	get_client_ip();
	    $time	=	time();
	    $data = array();
	    $data['id']	=	$u['id'];
	    $data['lastlogin_time']	=	$time;
	    $data['lastlogin_ip']	=	$ip;
	    $data['login_count']	=	array('exp','login_count+1');
	    $this->save($data);
	    $this->getLastSql();
	}
	
	/**
	 * 绑定vpn账号到每个设备上
	 * @param unknown $id
	 * @param unknown $deviceId
	 * @param unknown $accountInfo
	 * @return boolean
	 */
	function bindVpnAccountToDevice($id,$deviceId,$accountInfo){
	    $r = $this->find($id);
	    if($r['extra']){
	        $extra = json_decode($r['extra']);
	    }else{
	        $extra = [];
	    }
	    $extra[] = ['device_id' => $accountInfo];
	    $this->extra = $extra;
	    return $this->save();
	}
	
}


?>