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
	/** @var Dkj_UserRepository	 */
	private $userRepository;


	public function __construct(Dkj_UserRepository $userRepository)
	{
		$this->userRepository = $userRepository;
	}


	public function create($label = null)
	{
		$input = new Selectoo($label ?? 'User', function() {
			return $this->userRepository->fetchUsers(); // returns pairs [ id => name ]
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
