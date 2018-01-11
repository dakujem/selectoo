<?php


namespace Dakujem\Selectoo\Examples;

use Nette\Application\LinkGenerator;


/**
 * Example Selectoo input factory:
 *
 * - selects a single user from a list retrieved form an API call using AJAX
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
class Dkj_UserAjaxSelectooFactory
{
	/** @var Dkj_UserRepository	 */
	private $userRepository;

	/** @var LinkGenerator	 */
	private $lg;


	public function __construct(Dkj_UserRepository $userRepository, LinkGenerator $lg)
	{
		$this->userRepository = $userRepository;
		$this->lg = $lg;
	}


	public function create($label = null)
	{
		$input = new Selectoo($label ?? 'User', function($rawValue = null) {
			return $this->userRepository->fetchUsers($rawValue); // returns pairs [ id => name ]
		}, false);

		$link = $this->lg->link('Example:api');
		$engine = new Select2Engine();
		$engine
				->ajax('{url: "' . $link . '", dataType: "json" }')
				->placeholder('Select a user', true)
				->width('width: 100%', true)
		;
		$input->setEngine($engine);

		return $input;
	}

}
