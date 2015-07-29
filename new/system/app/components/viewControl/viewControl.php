<?php
namespace Agility;
use Nette;

class viewControlControl extends Nette\Application\UI\Control
{
    /** @var \Nette\ArrayHash */
    protected $atr;

    /** @persistent */
    public $filter = NULL;
    
    /** @persistent */
    public $orderBy = NULL;
    
    
    /**  @persistent  */
    public $order;
    
    public function __construct($presenter,$name){
	parent::__construct($presenter,$name);
	 $this->atr = \Nette\ArrayHash::from(array());
    }
    
    

    /**
     * 
     * @return array (filter, orderBy)
     */
    public function getSettings(){
	/** in config is not set sortByDefault and no sorting is given, use default  */
	$orderBy = 'posted';
	$order = 'DESC';
	$valid=array(
	    'posted'=>true,
	    'sortable1'=>true,
	    'sortable2'=>true,
	    'sortable3'=>true
		);
	/** at first - if user want to sort by some column */
	if(isset($valid[$this->orderBy])){
	    // get column
	    $orderBy = $this->orderBy;
	    // get way - if given, use it, if not, use default
	    if(!empty($this->order)){
		$order = strtoupper($this->order) == 'ASC'?'ASC':'DESC';
	    } // posted is default DESC
	    else if($orderBy != 'posted'){
		$order = $this->getPresenter()->pageConfig['settings'][$orderBy]['order'];
	    }
	}/** user did not specify column, but maybe is something in config - check it */
	else if(isset($this->getPresenter()->pageConfig['settings']['sortByDefault'])){
	    $orderBy = $this->getPresenter()->pageConfig['settings']['sortByDefault'];
	    $order = $this->getPresenter()->pageConfig['settings'][$orderBy]['order'];
	}
	
	return array($this->filter, $orderBy,$order);
    }
     
    public function render()
    {
	$this->template->setFile(__DIR__ . '/viewControl.latte');
	$this->template->presenter = $this->presenter;
	
	$this->template->filter = $this->filter;
	
	
	foreach($this->atr as $key =>$val){
	    $this->template->$key = $val;
	}
	
        $this->template->render();
    }
    
    
    public function addAtr( \Nette\ArrayHash $atr){
	foreach($atr as $key =>$val){
	    $this->atr->$key = $val;
	}
    }
    
}