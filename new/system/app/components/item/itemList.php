<?php
namespace Agility;
use Nette;

class ItemListControl extends Nette\Application\UI\Control
{
    /** @var \Nette\Database\Table\Selection */
    private $selected;
    /** @var \Nette\ArrayHash */
    private $atr;
    /** @var boolean */
    private $onlyOne;

    /** Create items in page. 
     * If $onlyOne is set to true, expect only one item in $selected.
     * 
     * @param Nette\Database\Table\Selection $selected
     * @param \Nette\ArrayHash $atr
     * @param boolean $onlyOne
     */
    public function __construct(Nette\Database\Table\Selection $selected, \Nette\ArrayHash $atr, $onlyOne = false)// $page,)
    {
        parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->selected = $selected;
        $this->onlyOne = $onlyOne;
        $this->atr = $atr;
    }
    
    public function beforeRender(){
	
    }
     
    public function render()
    {
	$this->template->page = $this->atr->page;
	$this->template->filters = $this->atr->filters;
	$this->template->isAdmin = $this->atr->isAdmin;
	/** select if we want only one or all items */
	if($this->onlyOne){
	    $this->template->setFile(__DIR__ . '/'.$this->atr->page.'.latte');
	    $this->template->data = json_decode($this->selected->content); 
	    $this->template->shade = false;
	    if(gettype($this->template->data->date) !="string") 
		$this->template->data->date = $this->template->data->date->date;
	    if($this->atr->shadePast && strtotime($this->template->data->date) < strtotime(date('Y-m-d'))) {
		$this->template->shade = true;
		$this->template->isOld = true;
	    }
	    $this->template->item = $this->selected; 
	    
	}
	else{
	    $this->template->setFile(__DIR__ . '/list.latte');
	    $this->template->items = $this->selected;
	    $this->template->shadePast = $this->atr->shadePast;
	}
	
	$helpers = new \Agility\Helpers();
	$this->template->registerHelperLoader(array($helpers, 'loader'));
	
	
        $this->template->render();
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
	# zakázaní nadpisů
	$texy->allowed['heading/surrounded'] = FALSE;
	$texy->allowed['heading/underlined'] = FALSE;

	# zalamování textu v odstavcích po enteru
	# false => nebude spojovat řádky, vloží místo enteru <br>
	# true => řádky po jednom enteru spojí
	$texy->mergeLines = false;
	
	$texy->linkModule->forceNoFollow = TRUE;            // force rel="nofollow"
	$template->registerHelper('texy', callback($texy, 'process'));

	return $template;
    }
    
}