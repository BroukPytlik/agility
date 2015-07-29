<?php

use Nette\Application\UI\Form;

/**
 * Style presenter - for creating special CSSs
 */
class StylePresenter extends BaseNormalPresenter {

    /** @var array - variables translation */
    protected $cssVariables=array();
    protected $allCssVariables = array();
    protected function startup() {
	parent::startup();
	$this->context->httpResponse->setContentType('text/css', 'UTF-8');
	$this->jsFiles = array(); // empty it
	$this->cssFiles = array(); // empty it
    }
    
    
    public function actionDefault() {
	$this->loadAllCssConfig();
    }


    public function beforeRender() {
	parent::beforeRender();
	//$this->template->cssVariables = $this->cssVariables;
	$this->template->allCssVariables = $this->allCssVariables;
    }

    /** will load colors from .neon and set to css filter.
     * If $page is not set, then it load default colors, else it will load 
     * specific page colors.
     * 
     * @param string $page
     */
    protected function loadAllCssConfig(){
	$params = $this->context->parameters;
	foreach (array_keys($params['pageLayout']) as $page ) {
	    $this->allCssVariables[$page] = $this->loadCssConfig($page);
	}
	
	//foreach($config as $key => $val){
	//    $this->cssVariables[$key]=$val;
	//}
    }

}
