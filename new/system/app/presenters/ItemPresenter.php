<?php

use Nette\Application\UI\Form;

/**
 * item presenter
 * @persistent(vc)
 */
class ItemPresenter extends ItemsBasePresenter {

    public function actionNew($item) {
	$this->pageUrl = $item;
	/** check if page is known or 404 should be thrown */
	if (isset($this->pages[$item])) {
	    $this->pageConfig = $this->pages[$item];
	} else {
	    $this->pageConfig = NULL;
	    $this->setView('notFound');
	}


	/** setup viewControl */
	$this->viewControl->addAtr(\Nette\ArrayHash::from(array(
		    'page' => $this->pageConfig,
		    'pageUrl' => $this->pageUrl,
		    'isAdmin' => $this->isAdmin(),
		    'isCalendar' => false
		)));

	if ($this->isAjax()) {
	    $this->invalidateControl('main-content');
	    $this->invalidateControl('header-navigation');
	    $this->invalidateControl('header-link');
	    $this->payload->page = $item;
	}
    }

    public function actionDefault($item) {
	if ($item == NULL) {
	    reset($this->pages);
	    $item = key($this->pages);
	    $this->redirect('this', array('item' => $item));
	    $this->terminate();
	}
	$this->pageUrl = $item;
	/** check if page is known or 404 should be thrown */
	if (!empty($this->pages[$item])) {
	    $this->pageConfig = $this->pages[$item];
	} else {
	    $this->pageConfig = NULL;
	    throw new \Nette\Application\BadRequestException;
	}



	/** setup viewControl */
	$this->viewControl->addAtr(\Nette\ArrayHash::from(array(
		    'page' => $this->pageConfig,
		    'pageUrl' => $this->pageUrl,
		    'isAdmin' => $this->isAdmin(),
		    'isCalendar' => false
		)));
	list ($this->filter, $this->orderBy,$this->order) = $this->viewControl->getSettings();
	// if we are loading main page for section, it is possibly loading from
	// another presenter, so make full content load
	$query = $this->getHttpRequest()->url->query;
	if ($this->getHttpRequest()->getReferer() != $this->link('this') ) {
	    if ($this->isAjax()) {
		$this->invalidateControl('header-navigation');
		$this->invalidateControl('main-content');
		$this->invalidateControl('sidebar');
		$this->invalidateControl('header-link');
	    }
	} else if ($this->isAjax()) {
	    $this->invalidateControl('main-content');
	    $this->invalidateControl('header-navigation');
	}

	/** create conditions for selection */
	$conditions = $this->createConditions();

	/** create array with order texts and values for template */
	$this->createOrder($item);

	/** set other settings that was created later */
	$this->viewControl->addAtr(\Nette\ArrayHash::from(array(
		    'filters' => $this->filters,
		    'ordered' => $this->ordered,
		    'order' => $this->order,
		    'orderLinks' => $this->orderLinks
		)));
	/** set up paginator */
	// items on page
	$ipp = 30;
	if (isset($this->pageConfig['settings']['itemsPerPage'])) {
	    $ipp = $this->pageConfig['settings']['itemsPerPage'];
	}
	/** set paginator */
	$this->vp = new Agility\VisualPaginator($this, 'vp');
	$this->paginator = $this->vp->getPaginator(); // new Nette\Utils\Paginator;
	$count = $this->itemRepository->getAll($item, NULL, $conditions)->count();
	$this->paginator->setItemCount($count);
	$this->paginator->setItemsPerPage($ipp);
	//$this->paginator->setPage($p); // actual page
	$this->template->paginatorPage = $this->paginator->getPage();


	/** get data from database  */
	$this->items = $this->itemRepository->getAll($item, $this->paginator, $conditions);

	if ($this->isAjax()) {
	    $this->invalidateControl('itemsList');
	    $this->invalidateControl('paginator');
	    $this->invalidateControl('viewFilter');
	    $this->invalidateControl('viewOrder');
	    $this->invalidateControl('flashes');
	    $this->payload->page = $item;

	    // $this->payload->url=str_replace($this->link('Homepage:default'),'',$this->link('this'));
	}
    }

    public function beforeRender() {
	parent::beforeRender();
	$this->viewControl->addAtr(\Nette\ArrayHash::from(array(
		    'canAdd' => $this->canAdd
		)));
	if ($this->isAjax()) {
	    // because we want to reset the jqueryui modal window
	    $this->invalidateControl('editForm');
	}
    }

    /**
     * delete old items if wanted
     */
    private function deleteOld() {
	$value = NULL;
	$column = $this->pageConfig['settings']['delete']['column'];
	if (isset($this->pageConfig['settings']['delete']['time'])) {
	    $value = new DateTime($this->pageConfig['settings']['delete']['time']);
	}
	if ($value != NULL)
	    $this->itemRepository->deleteBy($column . ' < ?', $value);
    }

    /** form for adding new item 
     * 
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentNewItemForm() {
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
			//$formItem->addRule(Form::VALID, 'Zadané datum není platné!');
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
		    if (gettype($params["hint"]) == 'string')
			$formItem->setAttribute('title', $params["hint"])
				->setOption('description', $params["hint"]);
		}
		/** add placeholder, if any */
		if (isset($params["placeholder"])) {
		    $formItem->setAttribute('placeholder', $params["placeholder"]);
		}
		/** set default value if any */
		if (isset($params["defaultValue"])) {

		    $formItem->setDefaultValue($params["defaultValue"]);
		}
	    }
	}
	if (!empty($this->pageConfig['settings']['sendEmail'])) {
	    $hintText = 'Tento email nebude zveřejněn; bude vám na něj zaslán odkaz na stránku, na které můžete zde zadané hodnoty zpětně upravit.';

	    $session = $this->getSession('agility');
	    $defaultVal = '@';//$session->email == NULL ? '@' : $session->email;

	    $form->addText('email', 'Váš email:', 40, 100)
		    ->setDefaultValue($defaultVal)
		    ->setType('e-mail')
		    ->addRule(Form::FILLED, 'Je nutné zadat email.')
		    ->addRule(Form::EMAIL, 'Nezadali jste platnou emailovou adresu.')
		    ->setAttribute('title', $hintText)
		    ->setOption('description', $hintText);
	}
	$form->addAntispam('kontrolni');
	$form->addSubmit('create', 'Vytvořit');
	$renderer = $form->getRenderer();
	$renderer->wrappers['controls']['container'] = 'div';
	$renderer->wrappers['pair']['container'] = 'dl';
	$renderer->wrappers['label']['container'] = 'dt';
	$renderer->wrappers['control']['container'] = 'dd';
	$form->getElementPrototype()->class('ajax');

	$form->onSuccess[] = $this->newItemFormSubmitted;

	return $form;
    }

    /** Catch submitted newItem
     * 
     * @param \Nette\Application\UI\Form $form
     * @throws InvalidStateException
     * @throws Exception
     */
    public function newItemFormSubmitted(Form $form) {
	/** test for permissions, if user can add new item */
	$permissions = $this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity()->getRoles() : NULL;
	if (isset($permissions[$this->pageUrl]) &&
		$this->pageConfig['settings']['lvlForAdding'] <= $permissions[$this->pageUrl] ||
		$permissions['admin'] || $this->pageConfig['settings']['lvlForAdding'] == 0
	) {
	    try {
		/** validate date inputs */
		$values = $form->getValues();
		unset($values['kontrolni']); // remove antispam
		unset($values['form_created']); // remove antispam
		foreach ($values as $key => &$value) {
		    // Because email is added to all forms and not in config,
		    //  it could cause error.
		    if ($key == 'email')
			continue;
		    $confItem = $this->pageConfig['form'][$key];
		    // if date is empty and this item is not mandatory
		    // also skip
		    if ($value == "" && (!isset($confItem['mandatory']) || !$confItem['mandatory'] ))
			continue;
		    if ($confItem['type'] == 'calendar') {
			// check for another conditions
			if (!empty($this->pageConfig["form"][$key]["greaterEqualThen"])) {
			    $cond = $this->pageConfig["form"][$key]["greaterEqualThen"];
			    if (strtotime($values[$cond]) > strtotime($value)) {
				$tmp = $value;
				$value = $values[$cond];
				$values[$cond] = $tmp;
				$this->flashMessage('Akce nemůže skončit dříve než začne! Opravte prosím data.', 'error');
				throw new Exception('badDateFormat');
				// dump("gg");
			    }
			}

			$value = preg_replace('/\s\s+/', '', $value);
			if ($this->itemRepository->isValidDate($value)) {
			    //$value = date('d.m.Y',$date)
			} else {
			    $this->flashMessage('Zadali jste neplatné datum pro položku ' .
				    $confItem['text'] . '!', 'error');
			    throw new Exception('badDateFormat');
			}
		    }
		}
		unset($value);

		// get email and save it also to session
		if (isset($values->email)) {
		    $session = $this->getSession('agility');
		    $session->email = $values->email;

		    $email = $values->email;
		} else {
		    $email = '';
		}
		/** create hash for editing link */
		$hash = sha1(microtime() . $email);
		/** add new item to DB */
		$this->itemRepository->add($this->pageUrl, $hash, $this->pageConfig, $this->getHttpRequest(), $values);
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
		    $this->calendarRepository->add($this->itemRepository->lastInsertId(), $dateFrom, $dateTo, $this->pageUrl);
		}
		/** auto delete items if wanted */
		if (isset($this->pageConfig['settings']['delete'])) {
		    $this->deleteOld();
		}

		/** sent email if it is enabled */
		if (!empty($this->pageConfig["settings"]["sendEmail"]) && $this->context->parameters['email']['send']) {
		    try {
			$this->sendMail($this->context->parameters['email']['from'], $email, "Potvrzení nového záznamu", 'newItem', array(
			    'title' => $values->title,
			    'values' => $values,
			    'hash' => $hash
			));
			$this->flashMessage($this->pageConfig["texts"]["successAdd"], 'success');
		    } catch(Exception $e) {
			$this->flashMessage("Záznam byl uložen, ale došlo k chybě při odesílání emailu - pokud budete potřebovat upravit záznamy, kontaktujte nás na agility.tulak.me.", 'error');
			\Nette\Diagnostics\Debugger::log('Email cannot be sent! '.$e->getMessage());
		    }
		}else{
		    $this->flashMessage($this->pageConfig["texts"]["successAdd"], 'success');
		}
		

		// invalidate cache
		$cache = \Nette\Environment::getCache('Nette.Templating.Cache');
		$cache->clean(array(
		    \Nette\Caching\Cache::TAGS => array('items/' . $this->pageUrl),
		));

		if (!$this->isAjax()) {
		    // $this->redirect('this');
		} else {
		    $this->invalidateControl('list');
		    $this->invalidateControl('form');
		    $form->setValues(array(), TRUE);
		}
		$this->redirect('Item:default', array('item' => $this->pageUrl));
	    } catch (InvalidStateException $e) {
		$this->flashMessage($this->pageConfig["texts"]["failAdd"], 'error');
		throw $e;
	    } catch (Exception $e) {
		if ($e->getMessage() != 'badDateFormat') {
		    throw $e;
		}
	    }
	}
    }

}
