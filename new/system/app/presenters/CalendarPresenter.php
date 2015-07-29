<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// TODO Save actually selected date for calendar into template!

/**
 * calendar presenter
 * @persistent(vc)
 */
class CalendarPresenter extends ItemPresenter {

    /** @var */
    protected $conditions;
    
    protected $calendar = NULL;
    
    protected function startup(){
	parent::startup();
    }
    
    public function actionDefault($page){ 
	if($page=="vse"||$page=="all"||$page==NULL){
	    $this->pageUrl = NULL;
	}
	else if(isset($this->pages[$page])){
	    $this->pageUrl = $page;
	    $this->pageConfig = $this->pages[$page];
	}
	else {
	    $this->pageConfig = NULL;
	    $this->setView('notFound');
	}
	
	/** create viewControl */
	
	$this->viewControl->addAtr(\Nette\ArrayHash::from(array(
	    'canAdd' => $this->canAdd,
	    'page' => $this->pageConfig,
	    'pageUrl' => $this->pageUrl,
	    'isAdmin' => $this->isAdmin(),
	    'isCalendar' => true
	)));
	list ($this->filter, $this->orderBy,$this->order)= $this->viewControl->getSettings();
	$this->template->isCalendar =  true;
	
	
	

	/** create conditions for selection */
	$this->conditions = $this->createConditions();
	/** create array with order texts and values for template */
	$this->createOrder($page);
	
	
	/** set other settings that was created later */
	$this->viewControl->addAtr(\Nette\ArrayHash::from(array(
	    'filters'=>$this->filters,
	    'ordered' => $this->ordered,
	    'order' => $this->order,
	    'orderLinks' => $this->orderLinks
	)));
	
	$cal = $this->getComponent('calendar');
	$this->template->calendarDate = array('year'=>$cal->year,'month'=>$cal->month);

	
	if ($this->isAjax()){
	    $this->invalidateControl('main-content');
	    $this->invalidateControl('header-navigation');
	    $this->invalidateControl('header-link');
	    $this->invalidateControl('flashes');
	    $this->payload->page=$page;
	    $this->payload->isCalendar=true;
	
	    // because we want to reset the jqueryui modal window
	    $this->invalidateControl('editForm');
	
	}
	
    }
    public function createComponentCalendar() {
	$cal = new EventCalendar();
	$cal->setEvents(new \Agility\EventModel($this->calendarRepository,$this->itemRepository,  \Nette\ArrayHash::from(array(
	    'pageUrl'=>$this->pageUrl,
	    'conditions' => $this->conditions
	))));
	$cal->setTemplateVar(\Nette\ArrayHash::from(array(
	    'filters'=>$this->filters,
	    'isAdmin'=>$this->isAdmin()
	)));
	$cal->setLanguage(EventCalendar::CZECH); // české názvy měsíců a dnů
	$cal->setMode(EventCalendar::FIRST_MONDAY); // týden začne pondělkem
	$cal->setOptions(array("showBottomNav"=>FALSE));
	$cal->setOptions(array("topNavPrev"=>"Předchozí měsíc","topNavNext"=>"Následující měsíc"));
	
	return $cal;
    }
    
}