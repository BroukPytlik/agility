<?php

namespace Agility;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class EventModel extends \Nette\Object implements \IEventModel {
    
    /** @var \Agility\CalendarRepository */
    private $calendarRepository;
    /** @var \Agility\ItemRepository */
    private $itemRepository;
    

    /** @var string */
    private $atr;
    
    /** @var Nette\Database\Table\ActiveRow */
    private $monthData = NULL;

    /**
     * 
     * @param \Agility\CalendarRepository $calendarRepository
     * @param \Agility\ItemRepository $itemRepository
     * @param array $atr optional
     */
    public function __construct(\Agility\CalendarRepository $calendarRepository,\Agility\ItemRepository $itemRepository, $atr= NULL) {
	$this->calendarRepository = $calendarRepository;
	$this->itemRepository = $itemRepository;
	$this->atr = $atr;
    }

    
    
    /**
     * zjistí, zda pro daný den existuje událost
     * @return boolean
     */
    public function isForDate($year,$month,$day){
	return $this->calendarRepository->findByDayCount($year, $month, $day, isset($this->atr->pageUrl)?$this->atr->pageUrl:NULL);
    }
    
    /**
     * vrátí pole událostí pro daný den
     * @return array
     */
    public function getForDate($year,$month,$day){
	// get events for this day
	$found = $this->calendarRepository
		->findByDay($year, $month, $day, 
			isset($this->atr->pageUrl)?$this->atr->pageUrl:NULL);
	// load data for all events - cached
	$this->dataLoad($this->calendarRepository->getIdList());
	$items = array();
	
	// remove order conditions
	/** test if it is array with allowed content */
	if(!empty($this->atr->conditions)){
	    foreach($this->atr->conditions as $column => $setting){
		if(gettype($setting) == 'array' || gettype($setting) == 'object' && get_class($setting) == 'Nette\ArrayHash'){
		    /** if order is set */
		    if(!empty($setting['order']) && count($setting['order']) && count($column) )
			unset($this->atr->conditions[$column]);
		    continue;
		}
		else if(gettype($setting) == 'string'){
		    $where[$column] = $setting;
		}else{
		    throw new \Exception('Bad parameter conditions!.');
		}
	    }
	}
	
	$oneDayEvents = array();
	$multiDaysEvents = array();
	
	//while($item = $found->fetch()){
	foreach($found as $item){
	    $itemData = $this->monthData[$item->item_id];
	    // check for conditions, if they are valid
		$valid = true;
		foreach($this->atr->conditions as $column => $setting){
		    if(!empty($setting['value'])){
			if(isset($itemData->$column) && $itemData->$column != $setting['value']) {
			    $valid = false;
			    continue;
			}
		    }
		}
		if(!$valid)
		    continue;
		// ---------------------------
		//dump($this->monthData[$item->item_id]);
		$event =  \Nette\ArrayHash::from(array(
		    'item' => $item,
		    'id'=>$item->item_id,
		    'page'=>$itemData->page,
		    'data'=>json_decode($itemData->content)
		));
		// sort one and more days events
		if($item->to != NULL && $item->to != $item->from){
		    $multiDaysEvents[]=$event;
		}// only one day
		else{
		    $oneDayEvents[]=$event;
		}
	//dump($item->item_id);
	}
	return array_merge($multiDaysEvents,$oneDayEvents) ;
    }
    
    
    /**
     * Find data for given events
     * @param array(int) $ids
     * @return array
     */
    private function dataLoad($ids){
	// look if we already have found something
	if(!isset($this->monthData)){ // we dont have it yet, so select from DB
	    //dump("SELECT DATA FOR:");
	    //dump($ids);
	    $this->monthData = $this->itemRepository->getByIdList($ids,"*")->fetchPairs('id');
	    //dump($this->monthsActions[$month] );
	}
    }
    
}