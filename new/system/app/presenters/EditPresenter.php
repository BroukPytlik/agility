<?php

use Nette\Application\UI\Form;

/**
 * Item presenter.
 */
class EditPresenter extends BaseNormalPresenter {

    /** @var Agility\UserRepository */
    protected $userRepository;

    /** @var  - is used?? */
    protected $pageLayout;

    /** @var Nette\Database\Table\Selection - list of */
    protected $items;

    /** @var array */
    protected $editingItem = NULL;

    /** @var Nette\Database\Table\ActiveRow */
    protected $editingRow = NULL;

    /** @var - contain states like Jihomoravsky, Praha, ... */
    protected $filters = NULL;

    /** @var boolean - are we in admin edit, or user edit? */
    protected $adminEdit = false;

    /** @ for use with ajax admin edit to suppress flashes invalidation */
    private $noFlashes;

    protected function startup() {
	parent::startup();
	$this->userRepository = $this->context->userRepository;
    }

    /** edit form for normal users
     * 
     * @param string $hash - hash string from DB
     * @throws Nette\Application\BadRequestException
     */
    public function actionDefault($hash) {

	/** no hash can be shorter then 10 chars... so do not bother with testing */
	$table = NULL;
	if (strlen($hash) > 10) {
	    $table = $this->itemRepository
		    ->findBy(array(
		'authorHash' => $hash
		    ));
	}
	if ($table == NULL || $table->count() == 0) {
	    $this->flashMessage('Taková položka neexistuje!', 'error');
	    throw new \Nette\Application\BadRequestException;
	}

	$this->editingRow = $table->fetch();
	$this->editingItem = json_decode($this->editingRow->content);
	$this->pageUrl = $this->editingRow->page;
	/** check if page is known or an error should be thrown */
	//dump($this->editingRow);
	if (isset($this->pages[$this->pageUrl])) {
	    $this->pageConfig = $this->pages[$this->pageUrl];
	}

	$this->template->itemId = $this->editingRow->id;
	$this->template->authorHash = $this->editingRow->authorHash;
	$this->template->editingItem = $this->editingItem;
	if ($this->isAjax()) {

	    $this->payload->page=$this->pageUrl;
	    $this->invalidateControl('main-content');
	    $this->invalidateControl('editUserForm');
	}
    }

    /** edit form for admin users
     * 
     * @param string $hash - hash string from DB
     * @throws Nette\Application\BadRequestException
     */
    public function actionAdmin($id, $noFlashes = false) {
	$this->adminEdit = true;
	$table = $this->itemRepository
		->findBy(array(
	    'id' => $id
		));

	if ($table == NULL || $table->count() == 0) {
	    $this->flashMessage('Taková položka neexistuje!', 'error');
	    throw new Nette\Application\BadRequestException;
	}
	$this->editingRow = $table->fetch();
	$this->editingItem = json_decode($this->editingRow->content);
	$this->pageUrl = $this->editingRow->page;
	/** check if page is known or an error should be thrown */
	if (isset($this->pages[$this->pageUrl])) {
	    $this->pageConfig = $this->pages[$this->pageUrl];
	}
	// test for permissions
	if (!$this->isAdmin())
	    throw new \Nette\Application\ForbiddenRequestException;

	$this->template->itemId = $this->editingRow->id;
	$this->template->authorHash = $this->editingRow->authorHash;
	$this->template->editingItem = $this->editingItem;

	if ($this->isAjax()) {
	    /* this is because admin with ajax can also access the edit page
	     * so ID's of snippets could be in collision
	     */
	    $this->setView('ajax');
	    $this->invalidateControl('editAdminAjaxForm');

	    $this->payload->doNotSave = true;
	    $this->payload->openEditDialog = true;
	    $this->invalidateControl('editFlashes');
	}
    }

    /** Delete item - only for admins
     * 
     * @param type $id
     * @throws Nette\Application\ForbiddenRequestException
     */
    public function actionDelete($id) {
	if (!$this->isAdmin()) {
	    throw new Nette\Application\ForbiddenRequestException;
	}
	if ($id == NULL) {
	    throw new Nette\Application\BadRequestException;
	}
	$item = $this->itemRepository->findBy(array('id'=>$id))->fetch();
	$page = $item->page;
	$this->itemRepository->deleteById($id);
	$this->flashMessage('Smazáno.');
	
	// invalidate cache
	$cache = \Nette\Environment::getCache('Nette.Templating.Cache');
	$cache->clean(array(
	    \Nette\Caching\Cache::TAGS => array('items/'.$page),
	));
	
	$url = $this->getHttpRequest()->getReferer();
	$url->appendQuery(array(self::FLASH_KEY => $this->getParam(self::FLASH_KEY)));
	$this->redirectUrl($url->absoluteUrl);
    }

    public function beforeRender() {
	parent::beforeRender();
	/** list of available filters for select */
	$this->template->filters = $this->filters;
    }

    /** form for editing item 
     * 
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentEditItemForm() {
	$form = new Form();
	//$userPairs = $this->userRepository->findAll()->fetchPairs('id', 'name');
	if ($this->pageConfig) {
	    /** for each item from configuration for this page create form item
	     * 
	     */
	    foreach ($this->pageConfig["form"] as $item => $params) {
		$formItem = NULL;
		switch ($params["type"]) {
		    case "calendar":
			//$formItem = $form->addDate($item, $params['text'], Vodacek\Forms\Controls\DateInput::TYPE_DATE);
			//$formItem = $form->addText($item, $params['text'], 40, 100);
			$formItem = $form->addDatePicker($item, $params['text']);
			/** check for minimal date */
			$min = new DateTime('-100 years 00:00');
			$max = new DateTime('+100 years 00:00');
			if (isset($params["min"])) {
			    switch ($params["min"]) {
				case 'today':
				    $min = new DateTime('-0 days 00:00');
				    break;

				default:
				    $min = new DateTime($params["min"] . ' 00:00');
				    break;
			    }
			}
			/** check for maximal date */
			if (isset($params["max"])) {
			    switch ($params["max"]) {
				case 'today':
				    $max = new DateTime('+0 days 00:00');
				    break;

				default:
				    $max = new DateTime($params["max"] . ' 00:00');
				    break;
			    }
			}
			$formItem->setAttribute('min', $min->format('Y-m-d'))
				->setAttribute('max', $max->format('Y-m-d'));
			break;
		    case "shortText":
			$formItem = $form->addText($item, $params['text'], 40, 100);
			break;
		    case "longText":
			$formItem = $form->addTextArea($item, $params['text'], 40, 5);
			break;
		    case "location":
			$formItem = $form->addTextArea($item, $params['text'], 40, 3);
			break;
		    case "select":
			$formItem = $form->addSelect($item, $params['text'])
				->setItems($params["options"])
				->setPrompt('Vyberte');

			/** set default value for select if any */
			if (isset($params["defaultValue"])) {
			    $formItem->setDefaultValue($params["defaultValue"]);
			}
			break;
		}
		/** if it is mandatory set rule */
		if (isset($params["mandatory"]) && $params["mandatory"]) {
		    $formItem->addRule(Form::FILLED, 'Je nutné vyplnit "' . $params['text'] . '".');
		}
		/** if it has a hint, show it */
		if (isset($params["hint"])) {
		    $formItem->setOption('description', $params["hint"]);
		}

		/** set actual value if any */
		if (isset($this->editingItem->$item)) {

		    if ($params["type"] == 'calendar') {
			//$formItem->setDefaultValue();
			if (gettype($this->editingItem->$item) == 'object') {
			    $this->editingItem->$item = $this->editingItem->$item->date;
			}
		    }
		    $formItem->setDefaultValue($this->unescapeTags($this->editingItem->$item));
		}
	    }
	}
	if (!empty($this->pageConfig['settings']['sendEmail'])) {
	    $text = $this->adminEdit ? 'Email' : 'Váš email';
	    $form->addText('email', $text, 40, 100)
		    ->setDefaultValue($this->editingRow->authorEmail)
		    ->setDisabled(TRUE);
	}

	$form->addSelect('delete', 'Chcete tento záznam smazat?')
		->setItems(array('Ne', 'ANO'));


	$form->addAntispam('kontrolni', 'Následující políčko vymažte!', 'Jsi spamovací robot?!');

	$form->addSubmit('save', 'Uložit');
	$renderer = $form->getRenderer();
	$renderer->wrappers['controls']['container'] = 'div';
	$renderer->wrappers['pair']['container'] = 'dl';
	$renderer->wrappers['label']['container'] = 'dt';
	$renderer->wrappers['control']['container'] = 'dd';

	$form->getElementPrototype()->class('ajax');



	$form->onSuccess[] = $this->editItemFormSubmitted;
	return $form;
    }

    /** Catch submitted editItem
     * is used both by user and admin edit
     * 
     * @param \Nette\Application\UI\Form $form
     * @throws InvalidStateException
     * @throws Exception
     */
    public function editItemFormSubmitted(Form $form) {


	/** test for permissions, if user can add new item */
	$permissions = $this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity()->getRoles() : NULL;
	if (isset($permissions[$this->pageUrl]) &&
		$this->pageConfig['settings']['lvlForAdding'] <= $permissions[$this->pageUrl] ||
		$permissions['admin'] || $this->pageConfig['settings']['lvlForAdding'] == 0
	) {

	    try {
		$values = $form->getValues();
		unset($values['kontrolni']); // remove antispam
		unset($values['form_created']); // remove antispam
		/** if we want to delete, simply delete and skip validations */
		if (!empty($values->delete)) {
		    $this->itemRepository->delete($this->pageUrl, $this->editingRow->authorHash);
		    /** show message and redirect */
		    $this->flashMessage($this->pageConfig["texts"]["successRemove"], 'success');
		    $this->redirect('Item:default', array('item' => $this->pageUrl));
		} else {

		    foreach ($values as $key => &$value) {
			// Because email and delete is added to all forms and not in config,
			//  it could cause error.
			if ($key == 'email' || $key == 'delete')
			    continue;
			$confItem = $this->pageConfig['form'][$key];
			// if date is empty and this item is not mandatory
			// also skip
			if ($value == "" && (!isset($confItem['mandatory']) || !$confItem['mandatory'] ))
			    continue;
			if ($confItem['type'] == 'calendar') {
			    $value = preg_replace('/\s\s+/', '', $value);

			    if ($this->itemRepository->isValidDate($value)) {
				//$value = date('d.m.Y',$date);
			    } else {
				$this->flashMessage('Zadali jste neplatné datum pro položku ' .
					$confItem['text'] . '!', 'error');
				throw new Exception('badDateFormat');
			    }
			}
		    }
		    unset($value);

		    /** edit item in DB */
		    $this->itemRepository->update($this->pageUrl, $this->editingRow->authorHash, $this->pageConfig, $this->getHttpRequest(), $values);
		    // get last edited id
		    $lastId = $this->itemRepository->findByHash($this->editingRow->authorHash)->fetch()->id;
		    // also dont forget for calendar
		    if (isset($this->pageConfig['settings']['calendar'])) {
			// get from date
			$dateFrom = date('Y-m-d', strtotime($values[$this->pageConfig['settings']['calendar']['from']]));
			// get to date
			if (isset($this->pageConfig['settings']['calendar']['to'])) {
			    // if TO is set
			    $dateTo = date('Y-m-d', strtotime($values[$this->pageConfig['settings']['calendar']['to']]));
			} else if (isset($this->pageConfig['settings']['calendar']['duration'])) {
			    // if only duration is known
			    $dateTo = date('Y-m-d', strtotime(
					    $values[$this->pageConfig['settings']['calendar']['from']]
					    . ' + ' . $values[$this->pageConfig['settings']['calendar']['duration']]
					    . ' days'));
			} else {
			    // if TO date is not set
			    $dateTo = NULL;
			}
			$this->calendarRepository->update($lastId, $dateFrom, $dateTo);
		    }


		    /** show message and redirect */
		    if (!$this->noFlashes)
			$this->flashMessage($this->pageConfig["texts"]["successEdit"], 'success');


		    // invalidate cache
		    $cache = \Nette\Environment::getCache('Nette.Templating.Cache');
		    $cache->clean(array(
			\Nette\Caching\Cache::TAGS => array('items/' . $this->pageUrl),
		    ));

		    if (!$this->isAjax()) {
			//$this->redirect('this');
		    } else {
			$this->invalidateControl('editUserForm');
			$this->invalidateControl('editAdminForm');
			// send the edited ID
			$this->payload->editSaved = $lastId;
			//$form->setValues(array(), TRUE);
		    }
		}
	    } catch (InvalidStateException $e) {
		$this->flashMessage($this->pageConfig["texts"]["failEdit"], 'error');
		throw $e;
	    } catch (Exception $e) {
		if ($e->getMessage() != 'badDateFormat') {
		    throw $e;
		}
	    }
	}
    }

    // unescape &lt;/&gt; to < >
    private function unescapeTags($s) {
	return str_replace('&lt;', '<', str_replace('&gt;', '>', $s));
    }

    public function run(\Nette\Application\Request $request) {
	$response = parent::run($request);

	// dump($this->payload);   //v této chvíli už by payload měl být naplněn snippety a můžeš si s ním dělat co chceš

	return $response;
    }

}
