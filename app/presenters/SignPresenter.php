<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Tomaj\Form\Renderer\BootstrapRenderer;

class SignPresenter extends BasePresenter
{
	const
		MIN_CHARACTER_PASSWORD = '7';

	public $signInFactory;

	public $signUpFactory;

	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	private $database;

	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

	protected function createComponentSignInForm()
	{
		$form = new Form;
		$form->setRenderer(new BootstrapRenderer);
		$form->addText('nick', 'Username:')
			->setRequired('Please enter your username.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please enter your password.');

		$form->addCheckbox('remember', 'Remember me');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
	}


	public function signInFormSucceeded($form)
	{
		$values = $form->values;

		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->getUser()->login($values->nick, $values->password);
			$this->redirect('Homepage:');
		}
		catch (Nette\Security\AuthenticationException $e){
			$form->addError('Incorrect username or password.');
		}
	}


	/**
	 * Sign-up form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignUpForm()
	{
		$SignUp_form = new Nette\Application\UI\Form;
		$SignUp_form->setRenderer(new BootstrapRenderer);
		$SignUp_form->addText('nick', 'Nick:')
			->setRequired('Please write nick.');
		$SignUp_form->addText('mail', 'E-mail:')
			->setRequired('Please write email.')
			->addRule($SignUp_form::EMAIL, 'You write bad e-mail.');
		$SignUp_form->addPassword('password', '	Password:')
			->setRequired('Please write password.')
			->addRule($SignUp_form::MIN_LENGTH, 'Password must have min %d characters', self::MIN_CHARACTER_PASSWORD);
		$SignUp_form->addPassword('repeat_password', 'Repeat password:')
			->setRequired('Please repeat password')
			->addRule($SignUp_form::EQUAL, 'Password is no equal', $SignUp_form['password']);
		$SignUp_form->addSubmit('signUp', 'Register');
		$SignUp_form->addProtection('Timeout expired, resubmit the form');
		$SignUp_form->onSuccess[] = [$this, 'SignUpFormSuccessSubmited'];
		return $SignUp_form;
	}

	public function SignUpFormSuccessSubmited($SignUp_form)
	{
		$values = $SignUp_form->values;
		$flag = 0;
		/*************************************************
		select na vyhladanie èi a danı E-mail uz nepouíva
		 **************************************************/
		$selection = $this->database->table('users')->where('nick', $values->nick);
		foreach ($selection as $select) {
			$nick_DB = $select->nick;
		}
		if (isset($nick_DB))
		{
			$flag = 1;
			$SignUp_form->addError('Nick is already in use.');
		}

		/*************************************************
		select na vyhladanie èi a danı E-mail uz nepouíva
		 *************************************************/
		$selection = $this->database->table('users')->where('email', $values->mail);
		foreach ($selection as $select) {
			$mail_DB = $select->mail;
		}
		if (isset($mail_DB))
		{
			$flag = 1;
			$SignUp_form->addError('E-mail is already in use.');
		}

		if ($flag == 0) {
			$this->database->table('users')->insert([
				'nick' => $values->nick,
				'password' => md5(md5($values->password)),
				'email' => $values->mail,
			]);

			$this->flashMessage('You have been successfully registered.', 'success');
			$this->redirect('Homepage:');
		}
		else
		{
			$this->flashMessage('Registration failed.');
		}
	}

	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('Homepage:');
	}

}
