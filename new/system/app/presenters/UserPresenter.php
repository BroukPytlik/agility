<?php
use Nette\Application\UI\Form;
use Nette\Security as NS;

/**
 */
class UserPresenter extends BaseNormalPresenter
{
    /** @var Todo\UserRepository */
    private $userRepository;

    /** @var Todo\Authenticator */
    private $authenticator;
    private $editedUser = NULL;

    protected function startup()
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
        $this->userRepository = $this->context->userRepository;
        $this->authenticator = $this->context->authenticator;
	/**
	 * Continue...
	 */
    }
    
    public function beforeRender() {
	parent::beforeRender();
	$this->template->editingUser=($this->editedUser != NULL);
	if($this->isAjax()){
	    $this->invalidateControl('userContent');
	}
    }

    public function actionDefault(){
	if ($this->isAjax()) {
	    $this->invalidateControl('main-content');
	    $this->invalidateControl('header-navigation');
	    $this->invalidateControl('header-link');
	    //$this->payload->page='user';
	    //$this->invalidateControl('body');
	}
    }
    
    public function actionEdit($id = NULL){
	$this->template->username='';
	$this->template->users = $this->userRepository->findAll();
	if($id!=NULL){
	    $this->editedUser = $this->userRepository->findById($id)->fetch();
	    $this->template->username=$this->editedUser->username;
	}
	$this->invalidateControl('selecting');
    }
    
    /**
     * Create new user
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentNewUserForm()
    {
	$minChars = isset($this->context->parameters['passwordMinLength'])?$this->context->parameters['passwordMinLength']:6;
        $form = new Form();
        $form->addText('username', 'Uživatelské jméno', 30)
            ->addRule(Form::FILLED, 'Je nutné zadat uživatelské jméno');
	$form->addCheckbox('isAdmin', 'Administrátor');
        $form->addText('password', 'Heslo', 30)
            ->addRule(Form::MIN_LENGTH, 'Nové heslo musí mít alespoň %d znaků.', $minChars);
        $form->addSubmit('set', 'Vytvořit uživatele');
        $form->onSuccess[] = $this->newUserFormSubmitted;
        return $form;
    }
    public function newUserFormSubmitted(Form $form)
    {
        $values = $form->getValues();
        $user =  $this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity()->getRoles():NULL;
	if(!empty($user['admin'])){
	    $this->userRepository->createUser($values->username, $values->password,$values->isAdmin);
	    $this->flashMessage('Uživatel byl vytvořen.', 'success');
	    $this->redirect('User:');
	}else{
	    throw new Exception('Unauthorized access to newUserForm!');
	}
    }
     /**
     * Edit existing user
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentEditUserForm()
    {
	$minChars = isset($this->context->parameters['passwordMinLength'])?$this->context->parameters['passwordMinLength']:6;
	$form = new Form();
	/** get selected user data */
	if($this->editedUser != NULL){
	    $editedUser = $this->editedUser;
	    $userPerm = $this->permissionRepository->getLevels($editedUser->id)->fetchPairs('url');
	}else{
	    $editedUser = NULL;
	    $userPerm = NULL;
	}

	$form->addGroup('settings');
	$form->addText('username', 'Uživatelské jméno', 30)
		->setValue($editedUser ? $editedUser->username:'')
		->setDisabled();
	/** base user informations */
	$form->addHidden('editedId')
		->setValue($editedUser ? $editedUser->id : '');
	$form->addHidden('editedUsername')
		->setValue($editedUser ? $editedUser->username : '');
	$isA = $form->addCheckbox('isAdmin', 'Administrátor')
		->setValue($editedUser?$editedUser->isAdmin:false);
	if(!empty($editedUser) && $this->getUser()->getId() == $editedUser->id){
	    $isA->disabled = true;
	}
	$form->addText('password', 'Nové heslo', 30)
	    ->addConditionOn($form['password'], Form::FILLED, TRUE)
		->addRule(Form::MIN_LENGTH, 'Nové heslo musí mít alespoň %d znaků.', $minChars);

	/** permissions for pages */
	$form->addGroup('permission');
	foreach ($this->pages as $url => $page){
	    $level = $form->addSelect('PERM'.$url, $page['texts']['title'])
		->setItems(array(
		    0=>'[0] Normání návštěvník',
		    1=>'[1] Ověřený uživatel',
		    2=>'[2] Plná oprávnění'
		))
		->setOption('description', 'Pro přidávání na tuto stránku je zapotřebí úroveň '.$page['settings']['lvlForAdding']);;
		
		if($editedUser && isset($userPerm[$url])){
		    //dump($userPerm[$url]);
		    $level->setValue($userPerm[$url]->level);
		}
	}
	// delete section only if user cant delete itself
	
	    $form->addGroup('delete');
	   $del= $form->addSelect('deleteUser','Chtete uživatele smazat?')
		    ->setItems(array(
			0=>'Ne',
			1=>'ANO'
		    ));
	if(!empty($editedUser) && $this->getUser()->getId() == $editedUser->id){
	    $del->disabled = true;
	}
	$form->addGroup('confirm');
	$form->onSuccess[] = $this->editUserFormSubmitted;
	/** set ajax and if form should not be visible, hide it */
	$form->getElementPrototype()->class('ajax '.($this->editedUser == NULL?'hidden':''));
	$form->addSubmit('set', 'Upravit uživatele');
	$form->addGroup('delete');
	    
	return $form;
    }
    
    public function editUserFormSubmitted(Form $form)
    {
        $values = $form->getValues();
	/** test permissions */
        $user =  $this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity()->getRoles():NULL;
	if(!empty($user['admin'])){
	    if(isset($values->deleteUser) && $values->deleteUser == 1){
		$this->userRepository->delete($values->editedId);
		$this->flashMessage('Uživatel '.$values->editedUsername.' byl smazán.', 'success');
	    }else{
	    
		/** is changed password? */
		if(!empty($values->password)){
		    $this->userRepository->setPassword($values->editedId, $values->password);
		}
		/** save admin permission? */
		if(isset( $values->isAdmin) &&($this->getUser()->getId() != $values->editedId)){
		    $this->userRepository->setAdmin($values->editedId, $values->isAdmin);
		}

		/** set permissions for pages */
		foreach ($this->pages as $url => $page){
		    $level = $values['PERM'.$url];
		    $this->permissionRepository->setLevel($values->editedId, $url, $level);

		}

		/** redirect */

		$this->flashMessage('Uživatel '.$values->editedUsername.' byl upraven.', 'success');
		
	    }
	    /** redirect */
	    if (!$this->isAjax()) {
		$this->redirect('this');
	    } else {
		$this->invalidateControl('selected');
		$this->invalidateControl('selecting');
	    }
	    
	}else{
	    throw new Exception('Unauthorized access to selectUserForm!');
	}
    }
    
    /** change password
     * 
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentPasswordForm()
    {
        $form = new Form();
        $form->addPassword('oldPassword', 'Staré heslo:', 30)
            ->addRule(Form::FILLED, 'Je nutné zadat staré heslo.');
        $form->addPassword('newPassword', 'Nové heslo:', 30)
            ->addRule(Form::MIN_LENGTH, 'Nové heslo musí mít alespoň %d znaků.', $this->context->parameters['passwordMinLength']);
        $form->addPassword('confirmPassword', 'Potvrzení hesla:', 30)
            ->addRule(Form::FILLED, 'Nové heslo je nutné zadat ještě jednou pro potvrzení.')
            ->addRule(Form::EQUAL, 'Zadná hesla se musejí shodovat.', $form['newPassword']);
        $form->addSubmit('set', 'Změnit heslo');
        $form->onSuccess[] = $this->passwordFormSubmitted;
        return $form;
    }


    public function passwordFormSubmitted(Form $form)
    {
        $values = $form->getValues();
        $user = $this->getUser();
        try {
            $this->authenticator->authenticate(array($user->getIdentity()->username, $values->oldPassword));
            $this->userRepository->setPassword($user->getId(), $values->newPassword);
            $this->flashMessage('Heslo bylo změněno.', 'success');
            $this->redirect('Homepage:');
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Zadané heslo není správné.');
        }
    }
}