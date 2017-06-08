<?php
namespace Common\Model;
use  Think\Model\AdvModel;
class OrderModel extends AdvModel {
    protected $_validate = array(
        array('user_id','require','user_id是必须的'),
        array('project_id','require','project_id是必须的'),
        //array('project_id','checkProject','There is no product',0,'callback'),
    );

	/**
	 * 得到一条记录
	 * @param unknown_type $id
	 */
	public function getOne($id){
	    if(!is_numeric($id)) $this->error = "id为空";
	    $project = C('project');
	    $r = $project[$id];
	    //$r = $this->find($id);
	    return $r;
	}
	
	
	

	//插入，增加新订单
	public function insert($data){
	    $mProject = D('Project');
	    $r = $mProject->getOne($data['project_id']);
	    if(!$r){
	        $this->error = '产品不存在';
	        return false;
        }
	    $data['price'] = $r['price'];
	    // $paymethod = $data['paymethod'];
	    //支付宝转账服务时间+10%
	    // if($paymethod == 3) {
	    // 	$new_days = intval($r['days'] * 1.1);
	    // 	$r['expression'] = str_replace($r['days'], $new_days, $r['expression']);
	    // 	$r['days'] = $new_days;
	    // }
	    $data['snapshot'] = json_encode($r);
	    if(false === $this->create($data)){
	        return false;
        }
        return $this->add($data);
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
		$p = explode('/',$r['location']);
		//dump($p);
		$r['province'] = $p[0];
		$r['city'] = $p[1];
		
		$r['pubtime'] = date('Y-m-d',$r['pubtime']);
		$r['ctime'] = date('Y-m-d',$r['ctime']);
	}
 
	
}




?>