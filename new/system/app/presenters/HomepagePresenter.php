<?php

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BaseNormalPresenter {

    protected function startup() {
	parent::startup();
	reset($this->pages);
	$this->redirect ('Item:default', key($this->pages) );
    }
    

    public function renderDefault() {
        $this->template->anyVariable = 'any value';
	 if ($this->isAjax()) {
		$this->invalidateControl('main-content');
		$this->invalidateControl('header-navigation');
		$this->invalidateControl('header-link');
		$this->invalidateControl('flashes');
		//$this->invalidateControl('body');
	    }
    }

}
