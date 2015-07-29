<?php

use Nette\Application\UI;
use Nette\Application\UI\Form;

/**
 * Sign in/out presenters.
 */
class SignPresenter extends BaseNormalPresenter {

    
    /** @var Agility\UserRepository */
    protected $userRepository;
    
    protected function startup() {
	parent::startup();
	$this->userRepository = $this->context->userRepository;
    }
    
    /**
     * Sign-in form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSignInForm() {
        $form = new Form();
        $form->addText('username', 'Uživatelské jméno:', 30, 20);
        $form->addPassword('password', 'Heslo:', 30);
        $form->addCheckbox('persistent', 'Pamatovat si mě na tomto počítači');
        $form->addSubmit('login', 'Přihlásit se');
        $form->onSuccess[] = $this->signInFormSubmitted;
        return $form;
    }

    public function signInFormSubmitted(Form $form) {
        try {
            $user = $this->getUser();
            $values = $form->getValues();
            /*if ($values->persistent) {
                $user->setExpiration('+30 days', FALSE);
            }*/
	    /** make login */
            $user->login($values->username, $values->password);
	    /** get rights */
	    $userId = $user->getIdentity()->id;
	    $permissions = array();
	    foreach($this->permissionRepository->getLevels($userId)->fetchPairs('url') as $page => $level){
		$permissions[$page] = $level->level;
	    }
	    /** test for admin */
	    $permissions['admin'] = $this->userRepository->isAdmin($userId);
	    
	    /** set permissions */
	    $user->getIdentity()->setRoles($permissions);
	    
            $this->flashMessage('Přihlášení bylo úspěšné.', 'success');
            $this->redirect('Homepage:');
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Neplatné uživatelské jméno nebo heslo.');
        }
    }

    public function actionOut() {
        $this->getUser()->logout();
        $this->flashMessage('Byli jste odhlášeni.');
        $this->redirect('in');
    }

}
