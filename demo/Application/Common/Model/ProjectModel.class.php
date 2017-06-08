<?php
namespace Common\Model;
class ProjectModel extends CommonModel {
		
	
	
	
	/**
	 * 得到一条记录
	 * @param unknown_type $id
	 */
	public function getOne($id){
	    if(!is_numeric($id)) $this->error = "id为空";
	    //$project = C('project');
	    //$r = $project[$id];
	    $r = $this->find($id);
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
		$p = explode('/',$r['location']);
		//dump($p);
		$r['province'] = $p[0];
		$r['city'] = $p[1];
		
		$r['pubtime'] = date('Y-m-d',$r['pubtime']);
		$r['ctime'] = date('Y-m-d',$r['ctime']);
	}
	

	
}




?>