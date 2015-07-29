<?php
namespace Agility;
use Nette;

/**
 * Tabulka user
 */
class PermissionRepository extends Repository
{
    public function getLevels($userId){
	return $this->findBy(array('userId' => $userId));
    }
    public function setLevel($userId,$url,$level){
	/** at first try find something for update */
	if($this->findBy(array('userId'=>$userId,'url'=>$url))->count()){
	    $res = $this->getTable()
		->where(array(
		    'userId'=>$userId,
		    'url'=>$url
		))->update(array(
		    'level'=>$level
		));
	/** if nothing was found, then insert new row */
	}else{
	    $res = $this->getTable()->insert(array(
		'userId'=>$userId,
		'url'=>$url,
		'level'=>$level
		));
	}
	return $res;
    }
}