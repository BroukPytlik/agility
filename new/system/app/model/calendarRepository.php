<?php

namespace Agility;

use Nette;

/**
 * Tabulka calendar
 * 
 */
class CalendarRepository extends Repository {

    /** @var Nette\Database\Table\ActiveRow */
    private $monthActions = NULL;
    private $foundIDs = array();
    /**
     * Add new item to calendar
     * @param integer $id
     * @param datetime $from
     * @param datetime $to
     * @param string $page
     * @return type
     */
    public function add($id,$from,$to,$page){
	return $this->getTable()->insert(array(
	    'item_id'=>$id,
	    'item_page'=>$page,
	    'from'=>$from,
	    'to'=>$to
	    ));
    }
    
    /**
     * Update item in calendar
     * @param integer $id
     * @param datetime $from
     * @param datetime $to
     * @return type
     */
    public function update($id,$from,$to){
	return $this->findBy(array('item_id'=>$id))->update(array(
	    'from'=>$from,
	    'to'=>$to
	));
    }
    
    /**
     * Load all calendar events by date 
     * @param int $year
     * @param int $month
     * @return array
     */
    private function findByDayLoad($year,$month){
	// look if we already have found this month
	if($this->monthActions == NULL){ // we dont have it yet, so select from DB
	    //dump("FIND EVENTS IN MONTH");
	    $selection = $this->getTable()->select("*");
	    // find all items 
	    $from = "$year-$month-1";
	    $to = date('Y-m-d', strtotime("$year-$month-1 +1 month"));
	    $selection->where("from >= ?", $from)->where("from <= ?", $to)->where("to <= ? OR to IS NULL",$to);
	    //$selection->where("from <= ?",$date)->where("to >= ? OR to IS NULL",$date);
	    $this->monthActions = $selection->fetchPairs('item_id');
	    foreach($this->monthActions as $id=>$row){
		$this->foundIDs[$id] = true;
	    }
	    //dump($this->monthsActions[$month] );
	}
    }
    /**
     * Find all calendar events by date and page
     * @param int $year
     * @param int $month
     * @param int $day
     * @param string $page optional
     * @return array
     */
    public function findByDay($year,$month,$day,$page = NULL){
	$this->findByDayLoad($year, $month);
	$selected = array();
	
	
	foreach($this->monthActions as  $row) {
	  //  dump($row);
	    // if page is set but is not equeal, skip to next item
	    if($page != NULL && $row->item_page != $page){
		continue;
	    }
	    $forDay = strtotime("$year-$month-$day");
	    if( (strtotime($row->from) <= $forDay) 
		    && (strtotime($row->to) >= $forDay)
		    || ($row->to == NULL && strtotime($row->from) == $forDay)
	    ){
		
		$selected[] = $row;
	    }
	}
	// load data
	return $selected;
    }
    
    
    /**
     * Find all calendar events by date and page and return the count
     * @param int $year
     * @param int $month
     * @param int $day
     * @param string $page optional
     * @return array
     */
    public function findByDayCount($year,$month,$day,$page = NULL){
	$this->findByDayLoad($year, $month);
	// now select exactly dates
	//$selected = array();
	$count = 0;
	foreach($this->monthActions as $row) {
	  //  dump($row);
	    // if page is set but is not equeal, skip to next item
	    if($page != NULL && $row->item_page != $page){
		continue;
	    }
	    if( (strtotime($row->from) <= strtotime("$year-$month-$day")) 
		    && (strtotime($row->to) >= strtotime("$year-$month-$day" || $row->to == NULL)) 
		){
		$count++;
	    }
	}
	//return $selection;
	//dump($count);
	return $count;
    }
    
    /** return list of IDs found for this month */
    public function getIdList (){
	return array_keys($this->foundIDs);
    }
}