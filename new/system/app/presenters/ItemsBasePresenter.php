<?php

use Nette\Application\UI\Form;

/**
 * Item presenter.
 */
class ItemsBasePresenter extends BaseNormalPresenter {

    
    /** @var Agility\UserRepository */
    protected $userRepository;

    /** @var  - is used?? */
    protected $pageLayout;

    /** @var Nette\Database\Table\Selection - list of */
    protected $items;

    /** @var Nette\Utils\Paginator - paginator for automatic pages */
    protected $paginator = NULL;
    protected $vp = NULL;

    /** @var */
    public $filter = NULL;
    /** @var - contain states like Jihomoravsky, Praha, ... */
    protected $filters = NULL;

    /** @var */
    protected $orderLinks=array();

    /** @var */
    public $order;

    /** @var */
    public $orderBy;
    /** @var array */
    public $ordered = NULL;
    
    /** @var \Agility\viewControlControl */
    protected $viewControl = NULL;


    protected function startup() {
	parent::startup();
	if($this->isAjax()){
		$this->invalidateControl('editForm');
	}
	$this->viewControl = new Agility\viewControlControl($this,'vc');
	$this->userRepository = $this->context->userRepository;
	
    }
    
    /** Load settings from neon file
     * 
     * @return type
     */
    protected function loadSettings($page){
	/** get filter 
	 * At first detect where is the list:
	 * This can be some SELECT type in form,
	 * or in database.
	 */
	$settings = $filterSettings = $this->pages[$page]['settings'];
	if (isset($settings['filter'])) {
	    $filterSettings = $settings['filter'];
	    switch ($filterSettings['type']) {
		case 'form':
		    $this->filters = $this->pages[$page]['form'][$filterSettings['data']]['options'];
		    break;
		case 'database':
		    $this->filters = $this->itemRepository->getUniqueList($page, $filterSettings['data']);
		    break;
	    }
	}
	return $settings;
    }
    
    /** create conditions for select
     * 
     * @return array
     */
    protected function createConditions(){
	/** create conditions for selection */
	if($this->order == NULL) $this->order = 'DESC';
	if($this->orderBy == NULL) $this->orderBy  = 'title';
	
	$conditions = array(
	    $this->orderBy => array('order' => $this->order)
	);
	if(isset($this->pageConfig['settings']['hide'])){
	    $column = $this->pageConfig['settings']['hide']['column'];
	    if(isset($this->pageConfig['settings']['hide']['time'])){
		$value = new DateTime($this->pageConfig['settings']['hide']['time']);
	    }
	    if($value != NULL)
		$conditions[$column.' > ?'] = $value->format('Y-m-d 00:00:00');
	}
	
	if (!empty($this->filter)) {
	    
	    /** if filter is set */
	    if (!isset($conditions['sortable1']))
		$conditions['sortable1'] = array();
	    $conditions['sortable1']['value'] = $this->filter;
	}
	if($conditions == NULL){
	    $conditions = array();
	}
	return $conditions;
    }

    /** create ordering for select
     * 
     * @param type $item
     * @return type
     */
    protected function createOrder($item){
	if($this->orderBy  == NULL) $this->createConditions ();
	/** get filter 
	 * At first detect where is the list:
	 * This can be some SELECT type in form,
	 * or in database.
	 */
	$settings = $this->loadSettings($item);
	/** create array with order texts and values for template */
	 $order = array(
		    'column' => 'posted',
		    'text' => 'data přidání',
		    'order' => 'DESC'
		);
	$this->orderLinks[] = $order;
	/** for every possible sortable (they are 3) */
	for($i=1;$i<=3;$i++){
	    /** if both sortable and text for sortable is set */
	    if(!empty($settings['sortable'.$i]) && !empty($this->pages[$item]['texts']['sortable'.$i])){
		$order = array(
		    'column' => 'sortable'.$i,
		    'text' => $this->pages[$item]['texts']['sortable'.$i],
		    'order' =>  $settings['sortable'.$i]['order']
		);
		if($this->orderBy == 'sortable'.$i) $order['order'] = strtoupper($this->order) == 'DESC' ? 'ASC' :'DESC';
		$this->orderLinks[] = $order;
	    }
	}
	
	/** list of available order links */
	$this->template->orderLinks = $this->orderLinks;
	/** actual order method */
	$orderBy_tmp=$this->orderBy;
	if(!array_key_exists ($this->orderBy, $this->pages[$item]['texts'])){
		$orderBy_tmp="posted";
	}
	$this->ordered = array(
	    'text' => $orderBy_tmp == 'posted'? 'data přidání': $this->pages[$item]['texts'][$orderBy_tmp],
	    //'text' => $this->orderBy == 'posted'? 'data přidání': $this->pages[$item]['texts'][$this->orderBy],
	    'order' => strtoupper($this->order) == 'DESC' ? 'sestupně' :'vzestupně'
	    );

	$this->template->ordered=$this->ordered;
	
	return $order;
	
    }
    
    

    public function beforeRender() {
	parent::beforeRender();
	/** list of available filters for select */
	$this->template->filters = $this->filters;
	$this->template->filter = $this->filter;
	
    }
    
    
    
    /** this create component ItemList */
    protected function createComponentItemList() {
	if ($this->items === NULL) {
	    $this->error('Wrong action');
	}
	/** pageUrl can't be something else than is in layout.neon, 
	 * this is tested in defaultAction()
	 */
	$shade = isset($this->pageConfig['settings']['shadePast']) ? $this->pageConfig['settings']['shadePast'] : FALSE;
	return new Agility\ItemListControl($this->items, \Nette\ArrayHash::from(array(
		    'page' => $this->pageUrl,
		    'filters' => $this->filters,
		    'isAdmin' => $this->isAdmin(),
		    'shadePast' => $shade
		)));
    }

  
    
    /** this create component ItemList 
     * 
     * @return \Agility\viewControlControl
     */
    protected function createComponentViewControl() {
	if($this->viewControl == NULL)
	    $this->viewControl = $this->createViewControl();
	return $this->viewControl;
	
    }

    
}
