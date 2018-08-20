<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager implements Nette\Security\IAuthenticator
{
	use Nette\SmartObject;

	const
		TABLE_NAME = 'users',
		COLUMN_ID = 'id',
		COLUMN_NAME = 'nick',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_EMAIL = 'email';


	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($nick, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $nick)->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('The nick is incorrect.', self::IDENTITY_NOT_FOUND);
		}
		elseif ($row->password !== md5(md5($password))) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], "user", $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return void
	 * @throws DuplicateNameException
	 */
	public function add($nick, $email, $password)
	{
		try {
			$this->database->table(self::TABLE_NAME)->insert([
				self::COLUMN_NAME => $nick,
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
				self::COLUMN_EMAIL => $email,
			]);
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}

}



class DuplicateNameException extends \Exception
{}
