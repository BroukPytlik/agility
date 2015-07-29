<?php

use Nette\Application\UI\Form;

/**
 * Ancestor for all presenters that has access to normal layout
 */
class BaseNormalPresenter extends BasePresenter {
    
     protected function startup() {
	parent::startup();
	if ($this->isAjax()) {
	    $this->invalidateControl('ajaxStats');
	    $this->invalidateControl('title');
	    $this->invalidateControl('editAdminForm');
	    $this->payload->page='default';
	    //$this->invalidateControl('body');
	}
    }
    
    /** invalidation of flash messages for ajax
     * 
     */
    public function afterRender() {
	if ($this->presenter->isAjax() && $this->hasFlashSession())
	    $this->invalidateControl('flashes');
    }
    
    /** form for sending message
     * 
     * @return \Nette\Application\UI\Form
     */
    public function createComponentSendMessageForm() {
	$form = new Form();
	$form->addText('email', 'Váš email')
		->addRule(Form::EMAIL,'Email není platný')
		->addRule(Form::FILLED,'Musíte zadat svůj email.');
	$form->addTextArea('text', 'Váš vzkaz')
		->addRule(Form::FILLED,'Nelze odeslat prázdnou zprávu')
		->setAttribute('placeholder', 'Váš vzkaz pro nás.');
	$form->addAntispam('kontrolni', 'Následující políčko vymažte!', 'Jsi spamovací robot?!');
	$form->addSubmit('send', 'Odeslat');
	$form->getElementPrototype()->class('ajax');
	$form->onSuccess[] = $this->sendMessageFormSubmitted;
	
	return $form;
    }
    public function sendMessageFormSubmitted($form) {
	/** validate date inputs */
	$values = $form->getValues();
	try{
	$to = $this->context->parameters['email']['from'];
	$this->sendMail($values->email, $to , "Message from user", 'message', array(
			'text' => $values->text,
			'from'=>$values->email
		    ));
	$this->sendMail($to, $values->email, "Confirmation", 'messageConfirm', array(
			'text' => $values->text,
			'from'=>$values->email
		    ));
	}
	catch(Exception $e){
	    $this->flashMessage("Při odesílání došlo k chybě! Omlouváme se, ale posílání vzkazů je asi rozbité. :(", 'error');
	    \Nette\Diagnostics\Debugger::log('Email cannot be sent! '.$e->getMessage());
	    
	}
	$this->flashMessage("Váš vzkaz byl odeslán", 'success');
	if (!$this->isAjax()) {
		    $this->redirect('this');
	}
	else {
	    $form->setValues(array(), TRUE);
	    $this->invalidateControl('sidebar');
	}
    }
    
    protected function createTemplate($class = NULL)
    {
	$template = parent::createTemplate($class);
	$texy = new \Texy();
	$texy->encoding = 'utf-8';
        $texy->setOutputMode(\Texy::HTML5);
	// config as in \TexyConfigurator::safeMode($texy);
	$safeTags = array(
		'a'         => array('href', 'title'),
		'acronym'   => array('title'),
		'b'         => array(),
		'br'        => array(),
		'cite'      => array(),
		'code'      => array(),
		'em'        => array(),
		'i'         => array(),
		'strong'    => array(),
		'sub'       => array(),
		'sup'       => array(),
		'q'         => array(),
		'small'     => array(),
	);
	$texy->allowedClasses = \Texy::NONE;                 // no class or ID are allowed
	$texy->allowedStyles  = \Texy::NONE;                 // style modifiers are disabled
	$texy->allowedTags = $safeTags;			    // only some "safe" HTML tags and attributes are allowed
	$texy->urlSchemeFilters[\Texy::FILTER_ANCHOR] = '#https?:|ftp:|mailto:#A';
	$texy->urlSchemeFilters[\Texy::FILTER_IMAGE] = '#https?:#A';
	$texy->allowed['image'] = FALSE;                    // disable images
	$texy->allowed['link/definition'] = FALSE;          // disable [ref]: URL  reference definitions
	$texy->allowed['html/comment'] = FALSE;             // disable HTML comments
	$texy->linkModule->forceNoFollow = TRUE;            // force rel="nofollow"
	$template->registerHelper('texy', callback($texy, 'process'));

	return $template;
    }
}