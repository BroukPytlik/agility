<?php

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {

    
    
    /** @var Agility\PageRepository */
    protected $pageRepository;

    /** @var Agility\PermissionRepository */
    protected $permissionRepository;

    /** @var Agility\ItemRepository */
    protected $itemRepository;
    
    /** @var Agility\CalendarRepository */
    protected $calendarRepository;

    /** @var string */
    protected $pageUrl;

    /** @var int */
    protected $pageId;

    /** @var array - actual page - from $pages */
    public $pageConfig = NULL;

    /** @var array - all pages */
    protected $pages;
    
    /** @var bool */
    protected $isAdmin;
    
    /** @var array - list of files to be included in rendered page */
    protected $jsFiles=array();
    /** @var array - variables translation */
    protected $jsVariables=array();
    /** @var array */
    protected $jsFilesIE = array();
    /** @var array - list of files to be included in rendered page*/
    protected $cssFiles=array();
    /** @var array */
    protected $cssFilesPrint = array();
    /** @var array */
    protected $cssFilesOldIE = array();
    
    /** @var boolean */
    protected $canAdd = false;
    

    protected function startup() {
	parent::startup();
	
	\Stopwatch::start('BasePresenter');
	
	AntispamControl::register();
        AntispamControl::$minDelay = 2;  
	
	if($this->isAjax()){
	    $this->payload->doNotSave = false; 
	}
	
	
	//$this->getSession('agility')->setExpiration('+ 1 years'); // set up session
	
	$this->permissionRepository = $this->context->permissionRepository;
	$this->itemRepository = $this->context->itemRepository;
	$this->calendarRepository = $this->context->calendarRepository;
	$this->pages = $this->context->parameters["pageLayout"];
	
	
	
	
	    /** 
	     * Create list of used JS and CSS files 
	     */
	    $this->jsFiles = array(
		APP_DIR. '/templates/javascript/libs/jquery-1.8.2.min.js',
		APP_DIR. '/templates/javascript/libs/jquery-ui-1.9.1.custom.min.js',
		APP_DIR. '/templates/javascript/libs/transify-min.js',
		//APP_DIR. '/templates/javascript/libs/netteForms.jquery.js',
		APP_DIR. '/templates/javascript/libs/nette.ajax.js',
		APP_DIR. '/templates/javascript/libs/nette-extension/spinner.ajax.js',
		APP_DIR. '/templates/javascript/libs/nette-extension/diagnostics.dumps.ajax.js',
		APP_DIR. '/templates/javascript/libs/netteForms-live.js',
		APP_DIR. '/templates/javascript/libs/nette-extension/history.ajax.js',
		'init.js',
		'ui.js',
		'calendar.js'
		);
	    $this->jsFilesIE['mediaQuery'] = 'ie.mediaqueries.replacement.js';
	    $this->cssFiles = array(
		'jqueryui'=>'jquery-ui-1.9.1.custom.min.css',
		'calendart'=>'eventCalendar.less',
		'screen'=>'screen.less',
		'screen.layout'=>'screen.layout.less',
		'screen.content'=>'screen.content.less',
		'screen.colors'=>'screen.colors.less'
	    );
	    $this->cssFilesOldIE['IE']=APP_DIR. '/templates/less/screen.old.ie.less';
	    $this->cssFilesPrint['print'] = 'print.less';
	    if($this->getUser()->isLoggedIn()) 
		$this->jsFiles[] = 'admin.js';
	    
	
    }
    

   
    /** will load colors from .neon and set to css filter.
     * If $page is not set, then it load default colors, else it will load 
     * specific page colors.
     * 
     * @param string $page
     */
    protected function loadCssConfig($page){
	$params = $this->context->parameters;
	
	// if page is given, test if it realy exists and has colors!
	if((gettype($page) == 'string'  ) 
		&& isset($params['pageLayout'][$page]) 
		&& isset($params['pageLayout'][$page]['settings']['css'])){
	    $config = $params['pageLayout'][$page]['settings']['css'];
	}
	else 
	    throw new Nette\InvalidArgumentException('Given invalid parameter 
			    - must be string and name one of pages in config.');
	return $config;
    }

    /** show dialog for selection of layout
     * 
     * @return type
     */
    public function actionSelectLayout(){
	$this->setLayout('dialog');
	$this->template->title = 'Nastavení zobrazení';
    }
    
    /**
     * Before render - set up template variables, add helpers...
     * @return type
     */
    public function beforeRender() {
	parent::beforeRender();
	/** set visibility of newItem form */
	$this->canAdd = false;
	$permissions = $this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity()->getRoles() : NULL;
	if (isset($permissions[$this->pageUrl]) &&
		$this->pageConfig['settings']['lvlForAdding'] <= $permissions[$this->pageUrl] ||
		$permissions['admin'] || $this->pageConfig['settings']['lvlForAdding'] == 0
	) {
	    $this->canAdd = true;
	}
	$this->template->canAdd = $this->canAdd;
	
	$this->template->page = $this->pageConfig;
	$this->template->pages = $this->pages;
	$this->template->pageUrl = !empty($this->pageUrl)?$this->pageUrl:'default';
	$this->template->isAdmin = $this->isAdmin();
	
	$helpers = new \Agility\Helpers();
	$this->template->registerHelperLoader(array($helpers, 'loader'));
	
	\Stopwatch::stop('BasePresenter');
    }


    public function handleSignOut() {
	$this->getUser()->logout();
	$this->redirect('Sign:in');
    }

    /** Send mail.
     * 
     * @param string $from - who will be set as sender?
     * @param string $to - who should receive it
     * @param string $subject
     * @param string $templateName - templates/Email/$templateName.latte
     * @param mixed $params - is passed to template as it is, can be everything
     */
    public function sendMail($from, $to, $subject, $templateName, $params) {
	
	$template = $this->createTemplate();
	$template->setFile(__DIR__ .'/../templates/Email/' . $templateName . '.latte');
	
	$template->registerFilter(new Nette\Latte\Engine);
	$template->params = $params;
	$template->subject = $subject;
	$template->pageUrl = $this->pageUrl;

	$mail = new Nette\Mail\Message;
	$mailer = new Nette\Mail\SmtpMailer(array(
		    'host' => $this->context->parameters['email']['smtp'],
		    'username' => $this->context->parameters['email']['username'],
		    'password' => $this->context->parameters['email']['password'],
		    'secure' => $this->context->parameters['email']['secure'],
		));
	$mail->setFrom($from)
		->addTo($to)
		->setHtmlBody($template);
	//$mail->setMailer($mailer);
	$mail->send();
	
    }
    public function createComponentCss($cssFiles=NULL)
    {
	// if no given files
	$cssFiles = (gettype($cssFiles ) == 'array') ? $cssFiles : $this->cssFiles ;
	
	// připravíme seznam souborů
	// FileCollection v konstruktoru může dostat výchozí adresář, pak není potřeba psát absolutní cesty
	//$files = new \WebLoader\FileCollection(WWW_DIR . '/css');
	$files = new \WebLoader\FileCollection(APP_DIR . '/templates/less');
	$files->addFiles($cssFiles);
	// kompilátoru seznam předáme a určíme adresář, kam má kompilovat
	$compiler = \WebLoader\Compiler::createCssCompiler($files, WWW_DIR . '/webtemp');
	// because it has problem with cooperating...
	// there must be changed delimiter
	// and the filter also require something to initialize with
	
	$compiler->addFileFilter(new \Webloader\Filter\LessFilter());
	// nette komponenta pro výpis <link>ů přijímá kompilátor a cestu k adresáři na webu
	$css = new \WebLoader\Nette\CssLoader($compiler, $this->template->basePath . '/webtemp');
	$css->setMedia('screen');
	return $css;
    }
    
    public function createComponentCssPrint()
    {
	$css = $this->createComponentCss($this->cssFilesPrint);
	$css->setMedia('print');
	return $css;
    }
    
    
    public function createComponentCssOldIE()
    {
	$css = $this->createComponentCss($this->cssFilesOldIE);
	$css->setMedia('all');
	return $css;
    }
    
    public function createComponentCssHandheld()
    {
	$css = $this->createComponentCss($this->cssFilesPrint);
	$css->setMedia('handheld');
	return $css;
    }

    public function createComponentJs($givenFiles = NULL)
    {
	
	// if no given files
	$jsFiles = (gettype($givenFiles ) == 'array') ? $givenFiles : $this->jsFiles ;
	
	$files = new \WebLoader\FileCollection(APP_DIR.'/templates/javascript');
	// můžeme načíst i externí js
	//$files->addRemoteFile('http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js');
	$files->addFiles($jsFiles);
	
	$compiler = \WebLoader\Compiler::createJsCompiler($files, WWW_DIR . '/webtemp');
	
	$filter = new WebLoader\Filter\VariablesFilter(array('guu'=>'guu'));
	$filter->setDelimiter('__', '__');
	foreach($this->jsVariables as $key => $val){
	    $filter->$key=$val;
	}
	$compiler->addFilter($filter);
	
	$dev = !empty($this->context->parameters['developmentMode']);
	$compiler->setJoinFiles(!$dev);
	

	return new \WebLoader\Nette\JavaScriptLoader($compiler, $this->template->basePath . '/webtemp');
    }
    
    
    public function createComponentJsIE()
    {
	$js = $this->createComponentJs($this->jsFilesIE);
	return $js;
    }
    
    
    protected function isAdmin(){
	
	$isLogged = $this->getUser()->isLoggedIn();
	if($this->getUser()->getIdentity() && $isLogged){
	    $permissions = $this->getUser()->getIdentity()->getRoles();
	    if(isset($permissions['admin']) && intval($permissions['admin']) == true){
		return true;
	    }
	    else if(isset($permissions[$this->pageUrl])){
		if($permissions[$this->pageUrl] == 2)
		return true;
	    }
	}
	return false;
    }
}
