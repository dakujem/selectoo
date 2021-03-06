<?php


namespace Dakujem\Selectoo\Examples;

use Dakujem\Selectoo\ScriptEngineInterface,
	Dakujem\Selectoo\Select2Engine,
	Dakujem\Selectoo\Selectoo;


/**
 * Example Selectoo factory - all the scripts generated by engines are collected for later use.
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
class Dkj_Select2SelectooWithCollectorFactory
{
	/**
	 * @var Dkj_UiScriptManager
	 */
	private $collector;


	public function __construct(Dkj_UiScriptManager $uiScriptManager)
	{
		$this->collector = $uiScriptManager;
	}


	public function create($label = null, $items = null, $multiChoice = false, $engine = true)
	{
		$i = new Selectoo($label, $items, $multiChoice);

		if ($engine === true) {
			$engine = new Select2Engine();
		}
		if ($engine instanceof ScriptEngineInterface) {
			$i->setEngine($engine);

			$i->setScriptManagement(function($script) {

				// collect the script
				$this->collector->addScript($script);

				// return null to indicate the Selectoo instance that it should not manage the script by itself
				return null;
			});
		}

		return $i;
	}

}
