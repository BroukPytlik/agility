<?php

use Nette\Application\UI\Form;

/**
 * Ajax presenter - for ajax admin editing
 */
class AjaxPresenter extends ItemsBasePresenter {

    
    protected function startup() {
	parent::startup();
    }
    
    
    public function actionGetAll($data) {
	parent::actionDefault($data);
	
    }
    
    public function actionDelete($data) {
	if(!$this->isAdmin()) throw new Nette\Application\ForbiddenRequestException;
	$this->itemRepository->deleteById($data);
	$this->setView('default');
    }
    
    public function actionGetOne($data) {
	$this->items = $this->itemRepository->findBy(array('id'=>$data));
	$this->pageUrl = $this->items->fetch()->page;
	if (!empty($this->pages[$this->pageUrl])) {
	    $this->pageConfig = $this->pages[$this->pageUrl];
	}
	else {
	    $this->pageConfig = NULL;
	    throw new \Nette\Application\BadRequestException;
	}
	/** create conditions for selection */
	$conditions = $this->createConditions();
	
	/** create array with order texts and values for template */
	$this->createOrder($this->pageUrl);
	$this->invalidateControl('itemsList-itemsList');//'item-'.$data);
	
    }

    public function beforeRender() {
	parent::beforeRender();
	\Nette\Diagnostics\Debugger::$bar = FALSE;
    }


}
