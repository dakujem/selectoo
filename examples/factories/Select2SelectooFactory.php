<?php


namespace Dakujem\Selectoo\Examples;


/**
 * Example general Selectoo input factory.
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
class Dkj_Select2SelectooFactory
{
	private $scriptManagementRoutine;


	public function __construct($scriptManagementRoutine = null)
	{
		$this->scriptManagementRoutine = $scriptManagementRoutine;
	}


	public function create($label = null, $items = null, $multiChoice = false)
	{
		$input = new Selectoo($label, $items, $multiChoice);
		$input->setScriptManagement($this->scriptManagementRoutine);

		$engine = new Select2Engine();
		$engine
				->closeOnSelect(false, true)
				->placeholder($label, true)
				->width('width: 100%', true)
		;
		$input->setEngine($engine);

		return $input;
	}

}
