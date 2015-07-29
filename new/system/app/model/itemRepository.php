<?php

namespace Agility;

use Nette;

/**
 * Tabulka user
 * 
 */
class ItemRepository extends Repository {

    /**
     * Return all items for given page.
     * Conditions are array which are limiting the selected rows and settings
     * order.
     * 
     *	array(
     *	    'sortable1'=>array(
     *		'order'=>'ASC',
     *		'value'='something'
     *	    )
     *	);
     * 
     * @param string $page - url for which we are selecting
     * @param Nette\Utils\Paginator $paginator - url for which we are selecting
     * @param array $contitions - optional
     * @throws \Exception
     * @return Nette\Database\Table\Selection
     */
    public function getAll($page,$paginator,$conditions = NULL){
	
	
	if(empty($page)) throw new \Exception('Missing parameter page!.');
	
	$query = $this->findBy(array('page' => $page));
	if($paginator != NULL){
		$query->limit($paginator->getLength(), $paginator->getOffset());
	}
	$orders=array();
	$where=array();
	/** parse conditions  */
	if(gettype($conditions) == 'array'){
	    /** test if it is array with allowed content */
	    foreach($conditions as $column => $setting){
		if(gettype($setting) == 'array'){
		    /** if order is set */
		    if(!empty($setting['order']) && count($setting['order']) && count($column) ){
			$orders[] =$column.'  '.$setting['order'].' ';
		    }
		    /** if value is set */
		    if(!empty($setting['value'])){
			$where[$column] = $setting['value'];
		    }
		}
		else if(gettype($setting) == 'string'){
		    $where[$column] = $setting;
		}else
		    throw new \Exception('Bad parameter conditions!.');
	    }
	}else if($conditions != NULL) throw new \Exception('Bad parameter conditions!.');
	
	/** if there are some orders */
	if(count($orders)){
	    foreach($orders as $order)
		$query->order($order);
	}
	/** if there are some wheres */
	if(count($where)){
	    $query->where($where);
	}
	//dump($query->getSql());
	return $query;
    }
    /**
     * Add new item to db
     * 
     * @param string $page
     * @param type $hash
     * @param type $pageConfig
     * @param Nette\ArrayHash $contentArray
     * @return type
     */
    public function add($page, $hash, $pageConfig, \Nette\Http\Request $httpRequest, Nette\ArrayHash $contentArray) {
	/** move email outside of contentArray */
	$email = isset($contentArray['email'])?$contentArray['email']:'';
	unset($contentArray['email']);

	/** setting sortables 
	 * If is set, use adequate value, else set NULL.
	 */
	$sortable1 = !empty($pageConfig['settings']['sortable1']) 
		    ? $contentArray[$pageConfig['settings']['sortable1']['column']] : NULL;
	$sortable2 = !empty($pageConfig['settings']['sortable2']) 
		    ? $contentArray[$pageConfig['settings']['sortable2']['column']] : NULL;
	$sortable3 = !empty($pageConfig['settings']['sortable3']) 
		    ? $contentArray[$pageConfig['settings']['sortable3']['column']] : NULL;

	return $this->getTable()->insert(array(
		    'page' => $page,
		    'posted' => new \DateTime(),
		    'sortable1' => $sortable1,
		    'sortable2' => $sortable2,
		    'sortable3' => $sortable3,
		    'authorEmail' => $email,
		    'authorHash' => $hash,
		    'authorIP' => $httpRequest->getRemoteAddress().' ('.$httpRequest->getRemoteHost().')',
		    'content' => $this->escapeTags(json_encode($contentArray))
		));
    }
    /**
     * edit item in db
     * @param string $page
     * @param type $hash
     * @param type $pageConfig
     * @param Nette\ArrayHash $contentArray
     * @return type
     */
    public function update($page, $hash, $pageConfig, \Nette\Http\Request $httpRequest, Nette\ArrayHash $contentArray) {
	/** move email outside of contentArray */
	unset($contentArray['email']);

	/** setting sortables 
	 * If is set, use adequate value, else set NULL.
	 */
	$sortable1 = !empty($pageConfig['settings']['sortable1']) 
		    ? $contentArray[$pageConfig['settings']['sortable1']['column']] : NULL;
	$sortable2 = !empty($pageConfig['settings']['sortable2']) 
		    ? $contentArray[$pageConfig['settings']['sortable2']['column']] : NULL;
	$sortable3 = !empty($pageConfig['settings']['sortable3']) 
		    ? $contentArray[$pageConfig['settings']['sortable3']['column']] : NULL;

	return $this->findBy(array(
		    'authorHash' => $hash,
		    'page' =>$page
		))->update(array(
		    'sortable1' => $sortable1,
		    'sortable2' => $sortable2,
		    'sortable3' => $sortable3,
		    'editIP' => $httpRequest->getRemoteAddress().' ('.$httpRequest->getRemoteHost().')',
		    'content' => $this->escapeTags(json_encode($contentArray))
		));
    }
    
    /**
     * delete item from db
     * @param string $page - url of the page
     * @param type $hash
     * @return type
     */
    public function delete($page, $hash) {
	return $this->findBy(array(
		    'authorHash' => $hash,
		    'page' =>$page
		))->limit(1)->delete();
    }
    
    /**
     * delete item from db
     * @param integer $id - id of the page
     * @return type
     */
    public function deleteById($id) {
	return $this->findBy(array(
		    'id' => $id,
		))->limit(1)->delete();
    }
    /** check validity of date 
     * 
     * @param string $value
     * @return boolean
     */
    public function isValidDate($value) {

	return \DateTime::createFromFormat('d.m.Y', $value) ||
		\DateTime::createFromFormat('d.m. Y', $value) || 
		\DateTime::createFromFormat('d. m. Y', $value) || 
		\DateTime::createFromFormat('Y-m-d', $value)|| 
		\DateTime::createFromFormat('Y-m-d H:i:s', $value);
	
    }
    
    /** Return array of unique items in given column 
     * 
     * @param type $url
     * @param type $column
     * @return array
     */
    public function getUniqueList($url,$column){
	$result = array();
	$q = $this->getTable()->select('DISTINCT  '.$column.'')->where(array('page'=>$url));
	for($i=0;$i<$q->count();$i++){
	    $item=$q->fetch();
	    $result[$item[$column]]=$item[$column];
	}
	return $result;
    }
    /** delete items by given condition(s)
     * 
     * @param mixed $cond Array or string
     * @param mixed $val
     * @return type
     */
    public  function deleteBy($cond, $val){
	if(gettype($cond) == 'array'){
	    return $this->getTable()->where($cond)->delete();
	}
	else{
	    return $this->getTable()->where($cond,$val)->delete();
	}
	
    }
    
    public function findByHash($hash){
	return $this->findBy(array('authorHash'=>$hash));
    }
    
  
    // escape < and > to &lt;/&gt;
    private function escapeTags($s){
	return str_replace('<', '&lt;', str_replace('>', '&gt;', $s));
		
    }
    
    
    
    /**
     * Find data for given events
     * @param array(int) $ids
     * @param string $columns
     * @return \Nette\Database\Table\Selection
     */
    public function getByIdList($ids, $columns){
	$selection = $this->getTable()->select($columns);
	return $selection->where(array('id'=>$ids));
    }
}