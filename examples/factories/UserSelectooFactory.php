<?php


namespace Dakujem\Selectoo\Examples;


/**
 * Example Selectoo input factory:
 *
 * - selects a single user from a list stored in a database
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
class Dkj_UserSelectooFactory
{
	private $userRepository;


	public function __construct($userRepository)
	{
		$this->userRepository = $userRepository;
	}


	public function create($label = null)
	{
		$input = new Selectoo($label ?? 'User', function() {
			return $this->userRepository->fetchAllUsers(); // returns pairs [ id => name ]
		}, false);

		$engine = new Select2Engine();
		$engine
				->placeholder('Select a user', true)
				->width('width: 100%', true)
		;
		$input->setEngine($engine);

		return $input;
	}

}
