<?php
namespace Common\Model;
//class VpnModel {
class VpnModel extends CommonModel{
    
    protected $connection = array(
        'db_type'  => 'mysql',
        'db_user'  => 'vpn_web',
        'db_pwd'   => 'c0xb0gv29C',
        'db_host'  => '103.205.22.2',
        'db_port'  => '3306',
        'db_name'  => 'vpn_web',
        'db_charset' =>    'utf8',
    );
    
    //protected $connection = 'DB_VPN_USER';
   // protected $connection = 'mysql://root:123456@121.40.106.138:3306/vpn_web#utf8';
    
    protected $trueTableName = 'vpn_user_account';
    
    protected $_validate = array(
        array('username','','帐号名称已经存在！',0,'unique',1), 
        
    );
    protected $_auto = array (
        array('c_time','time',self::MODEL_INSERT,'function'), 
        array('last_online_time',0,self::MODEL_INSERT),
        array('node_id',0,self::MODEL_INSERT),
        
        
    );
    
    //自动为用户随机生成用户名(长度6-13)
    function create_password($length = 6){
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
        $password = '';
        for ( $i = 0; $i < $length; $i++ ){
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }
    
    function generate_username( $length = 6 ) {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789_';
        $password = '';
        for ( $i = 0; $i < $length; $i++ ){
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }
    
    /**
     * 创建vpn账号和密码
     * @param int $userId
     * @return 账号和密码
     */
    function generateAccount($userId){
        $username = 'user'.$userId.$this->generate_username(6);
        $password = $this->create_password(12);
        return ['username'=>$username,'password'=>$password];
    }
    
    
    //vpnCreateAccount
    
    /**
     * 创建vpn账号，返回用户名，密码
     * @param unknown $userId
     */
    function addAccount($userId,$expire_time){
         $account = $this->generateAccount($userId); 
         $data = $account;
         //$data['domain'] = $userId.C('VPN_DOMAIN');
         $data['expire_time'] = strtotime($expire_time);
         $data['userid'] = $userId;
         //$data['c_time'] = time();
         //$data['expire_sync_mask'] = 0;
         //$data['last_online_time'] = time();
         
        // curl_call($data);
         
         /*
         $id = $this->add($data);
         $m = M('SyncRecord');
         $data = [];
         $data['action'] = 'add';
         $data['vpn_user_id'] = $userId;
         $data['vpn_account_id'] = $id;
         $data['vpn_username'] = $account['username'];
         $data['vpn_password'] = $account['password'];
         $data['c_time'] = time();
         $data['m_time'] = time();
         
         $m->add($data);         
         return $account;
         */
    }
    
    /**
     * 创建服务器上的vpn账号,成功返回 1
     * @param unknown $data
     */
    function createVpnAccount2($data){
        return true;
        $data['a'] = 'add';
        $data['format'] = 'json';
        $url = 'http://103.235.169.107:91/vpnuser.php';
        // curl_call($data);
        $r = curl_get_content($url,$data);
        $r = json_decode($r,1);
        if(json_last_error() === JSON_ERROR_NONE){
            if($r['code'] == '0'){
                  return true;
            }
        }
        return false;
        //var_dump($r);
    }
    
    //更新vpn有效期
    //参数 ：账号，有效期
    function updateVpnEnd_time3($data){
        $data['format'] = 'json';
        $data['a']='renewal';
        $url = 'http://103.235.169.107:91/vpnuser.php';
        $r = curl_get_content($url,$data);
        $r = @json_decode($r,1);
        if(json_last_error() === JSON_ERROR_NONE){
            if($r['code'] == '0'){
                return true;
            }
        }
        return false;
    }
    
    //新增VPN账户
    public function createVpnAccount($data){
        
        $data['userid'] = $data['uid'];
        $data['expire_time'] = $data['expire'];
        $data['sync_mask'] = 0;
        
        if(false == $this->create($data)){
            $this->error($this->getError());
        }
        $account_id  = $this->add();
       /*  exit('x');f
        $syncData = [];
        $syncData['vpn_user_id'] = $data['uid'];
        $syncData['vpn_account_id'] = $account_id;
        $syncData['vpn_username'] = $data['username'];
        $syncData['vpn_password'] = $data['password'];
        $syncData['c_time'] = time();
        $syncData['m_time'] = time();
        $syncData["action"] = "add";
        
        $m = M('vpn_sync_record',null,$this->connection); */
        //$sync_id = $m->add($syncData);
        
    }
    
    
    //已到期用户续费成功 重新同步vpn账号
    public function updateVpnEnd_time($data){
        
        /* $data['uid'] = $user_id;
        $data['expire'] = $end_time;
 */        
        //获取用户VPN账号列表
        $vpn_user_list = $this->where(['userid' =>$data['uid']])->select();
        $vpn_account_id = $vpn_account_username = $vpn_account_password = array();
        if(empty($vpn_user_list)){
            \Think\Log::write('vpn账号不存在','ERR');
            $this->getError = 'vpn账号不存在';
            return false;
        }
        foreach ($vpn_user_list as $vpn_user) {
            //vpn账号id
            $vpn_account_id[] = $vpn_user["id"];
            //vpn账号用户名
            $vpn_account_username[] = $vpn_user["username"];
            //vpn账号密码
            $vpn_account_password[] = $vpn_user["password"];
        }
    
        //充值/续费成功重置同步标志更新过期时间
        $where = [];
        $where['id']  = array('in',$vpn_account_id);
        
        $data['expire_time'] = $data['expire'];
        //$data['sync_mask'] = 0;
        
        debug($data);
        //过期用户续费成功 重新同步VPN账号
        if( $data['type'] == 2) {
            $data['sync_mask'] = 0;
            $data['expire_sync_mask'] = 0;
             /*   //$table = new Table("vpn_sync_record");
               $m = M('vpn_sync_record',null,$this->connection);
              // addSyncRecordForRenewal($uid, $vpn_account_id, $vpn_account_username, $vpn_account_password);
        	$dataSync = array();
        	for ($i = 0; $i < count($vpn_account_id) ; $i++) { 
        		$dataSync["vpn_user_id"] = $data['uid'];
        		$dataSync["vpn_account_id"] = $vpn_account_id[$i];
        		$dataSync["vpn_username"] = $vpn_account_username[$i];
        		$dataSync["vpn_password"] = $vpn_account_password[$i];
        		$dataSync["c_time"] = time();
        		$dataSync["m_time"] = time();
        		$dataSync["action"] = "add";
        		$ret = $m->add($dataSync);
        	}
            return $ret; //返回最后一次添加状态，有漏洞，中间有失败则无法知悉 */
        	//return $ret->executeResult;  /**/
            
            //addSyncRecordForRenewal($uid, $vpn_account_id, $vpn_account_username, $vpn_account_password);
        }
        $r =  $this->where($where)->save($data);
        if($r === false){
            \Think\Log::write('更新时间出错:$sql','ERR');
        }
        $sql1 = $this->getLastSql();
        \Think\Log::write("sql :".$sql1,'ERR','', C('LOG_PATH')."order_sql.log");
        return $r;
    }
	
}


?>