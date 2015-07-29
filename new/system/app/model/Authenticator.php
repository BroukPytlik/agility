<?php

use Nette\Security,
    Nette\Utils\Strings;

/*
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'user id - auto increment',
  `username` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'username for login',
  `password` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'password  (hashed)',
  `isAdmin` tinyint(1) NOT NULL COMMENT 'is user admin?',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='table with users for more levels of permissions';
 */

/**
 * Users authenticator.
 */
class Authenticator extends Nette\Object implements Security\IAuthenticator {

    /** @var Nette\Database\Connection */
    private $database;
    
    /** @var Agility\UserRepository */
    protected $userRepository;

    //public function __construct(Nette\Database\Connection $database) {
    public function __construct(Agility\UserRepository  $database) {
        //$this->database = $database;
	$this->userRepository = $database;
    }

    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        list($username, $password) = $credentials;
        $row = $this->userRepository->findByName($username)->fetch();

	//dump(self::calculateHash($password, $row->password));
        if (!$row) {
            throw new Nette\Security\AuthenticationException("Uživatel '$username' nebyl nalezen.", self::IDENTITY_NOT_FOUND);
        }

        if ($row->password !== self::calculateHash($password, $row->password)) {
            throw new Nette\Security\AuthenticationException("Špatné heslo.", self::INVALID_CREDENTIAL);
        }

        unset($row->password);
        return new Nette\Security\Identity($row->id, NULL, $row->toArray());
    }

    /**
     * Computes salted password hash.
     * @param  string
     * @return string
     */
    public static function calculateHash($password, $salt = null) {
        if ($salt === null) {
            $salt = '$2a$07$' . Nette\Utils\Strings::random(32) . '$';
        }
        return crypt($password, $salt);
    }

}
