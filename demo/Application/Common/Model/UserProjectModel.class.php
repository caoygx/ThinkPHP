<?php
namespace Common\Model;
class UserProjectModel extends CommonModel {
		
/*     public $_auto		=	array(
        array('start_time','time',self::MODEL_INSERT,'function'),
        array('end_time','pwd',3,'callback'),
        // array('salt','autoSalt',1,'callback'),
        array('ip','get_client_ip',1,'function'),
    );
    
    function start_time(){
        return date('Y-m-d H:i:s',time());
    }
    
    function end_time(){
        return date('Y-m-d H:i:s',strtotime($projectInfo['expression']));
    }   */  
    
    
	/**
	 * 开通用户新计划
	 * @param unknown_type $id
	 */
	public function open($user_id,$projectInfo){
	   
	    $rUserProject = $this->getByUser_id($user_id);
	    
	    if($rUserProject){
	        $this->end_time = date('Y-m-d H:i:s',strtotime($projectInfo['expression'],strtotime($rUserProject['end_time'])));
	        return $this->save();
	    }else{
	        //调用接口
	        //callVpnAddUser();
	        
	        $data = [];
	        $data['user_id'] = $user_id;
	        $data['start_time'] = date('Y-m-d H:i:s',time());
	        $data['end_time'] = date('Y-m-d H:i:s',strtotime($projectInfo['expression']));
	        return $this->add($data);
	    }
	    
	}
	
	
	//添加新vpn用户
	//给vpn用户续费
	/**
	 * 添加用户计划
	 * @param unknown $user_id
	 * @param unknown $projectInfo
	 * @return mixed|boolean|unknown|string
	 */
	function insert($user_id,$projectInfo){
	    $dataUP = [];
	    $dataUP['user_id'] = $user_id;
	    $dataUP['start_time'] = date('Y-m-d H:i:s',time());
	    $dataUP['end_time'] = date('Y-m-d H:i:s',strtotime($projectInfo['expression']));
	    $dataUP['type'] = $projectInfo['type'];
	    
	    //curl_call();// 调用远程vpn更新接口
	    return $this->add($dataUP);
	}
	
	/**
	 * 购买后，给用户发货：更新用户计划时间
	 * @param unknown $user_id
	 * @param unknown $end_time
	 * @param number $type
     * @param bool $is_free
	 * @return boolean
	 */
	function update($user_id,$projectInfo,$is_free=false){
	    $r = $this->where(['user_id' => $user_id,'type' =>$projectInfo['type'] ])->find();
	    $currentTime = time();
	    $last_end_timestamp = strtotime($r['end_time']);
	    $end_time_base = max($currentTime ,$last_end_timestamp);
	    $new_end_time = date('Y-m-d H:i:s',strtotime($projectInfo['expression'],$end_time_base));
	    $where = [];
	    $where['user_id'] = $user_id;
	    $where['type'] = $projectInfo['type'];
	    $rUpdate = $this->where($where)->setField('end_time',$new_end_time);
        $sql1 = $this->getLastSql();
        \Think\Log::write("sql :".$sql1,'ERR','', C('LOG_PATH')."order_sql.log");
	    //curl_call();// 调用远程vpn更新接口
        /**
         * @var $vpn \Common\Model\VpnModel
         */
	    $vpn = D('Vpn');
	    $data = [];
	    if($currentTime > $last_end_timestamp){ //过期
	        $data['type']=2;
	    }else{
	        $data['type']=1;
	    }

        $data['uid'] = $user_id;
        $data['expire'] = strtotime($new_end_time);

        $rVpn = $vpn->updateVpnEnd_time($data);
        if($rVpn === false){
            $this->error = $this->getError();
        }

	    //免费用户更改为收费用户
	    $mUser = M('User');
	    $mUser->find($user_id);
	    if(empty($mUser->level) && !$is_free) $mUser->level = 1;
	    $mUser->save();
	    
	    return $rUpdate;
	}


    //获取用户某个类型产品的剩余天数
    function getRemainDays($user_id,$type=1){
	    $r = $this->where(['user_id' => $user_id,'type' => 1])->find();
        $difference = strtotime($r['end_time']) - time();
        if($difference <= 0){
            return 0;
        }else{
            return  ceil($difference / 86400);
        }
    }
	
}




?>