<?php

namespace Agility;

use Nette;

/**
 * Tabulka user
 */
class UserRepository extends Repository {
    
    /**
     * Find user by username
     * @param type $username
     * @return Nette\Database\Table\Selection
     */
    public function findByName($username) {
	return $this->findAll()->where('username', $username);
    }
    /**
     * Find user by id
     * @param type $id
     * @return Nette\Database\Table\Selection
     */
    public function findById($id) {
	return $this->findAll()->where('id', $id);
    }
    

    /**
     * Return true, if user is admin
     * @param type $id
     * @return boolean
     */
    public function isAdmin($id) {
	return $this->findBy(array('id' => $id))->select('isAdmin')->fetch()->isAdmin;
    }
    /**
     * Return true, if user is admin
     * @param type $id
     * @return Nette\Database\Table\Selection
     */
    public function setAdmin($id,$isAdmin) {
	return $this->getTable()->where(array('id' => $id))->update(array(
	    'isAdmin' => $isAdmin
	));
    }

    /**
     * set new passwordfor this user
     * 
     * @param type $id
     * @param type $password
     * @return Nette\Database\Table\Selection
     */
    public function setPassword($id, $password) {
	return $this->getTable()->where(array('id' => $id))->update(array(
	    'password' => \Authenticator::calculateHash($password)
	));
    }
    
    /**
     * set new passwordfor this user
     * 
     * @param type $id
     * @param type $password
     * @param type $isAdmin
     * @return Nette\Database\Table\Selection
     */
    public function editUser($id, $password,$isAdmin) {
	return $this->getTable()->where(array('id' => $id))->update(array(
	    'password' => \Authenticator::calculateHash($password),
	    'admin'=>$isAdmin
	));
    }
    
    /**
     * create new user
     * 
     * @param type $username
     * @param type $password
     * @param type $isAdmin
     * @return Nette\Database\Table\Selection
     */
    public function createUser($username, $password, $isAdmin) {
	return $this->getTable()->insert(array(
		    'username' => $username,
		    'password' => \Authenticator::calculateHash($password),
		    'isAdmin' => $isAdmin
		));
    }

    /** delete user
     * 
     * @param type $userId
     * @return Nette\Database\Table\Selection
     */
    public function delete($userId){
	return $this->getTable()->where(array('id'=>$userId))->delete();
    }
}
