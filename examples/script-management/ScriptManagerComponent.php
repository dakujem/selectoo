<?php


namespace Dakujem\Selectoo\Examples;

use Nette\Application\UI\Control;


/**
 * An example of a component that prints all the script from a collector to a page.
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
class Djk_ScriptManagerComponent extends Control
{
	/**
	 * @var UiScriptManager
	 */
	private $manager;


	public function setManager(Dkj_UiScriptManager $manager)
	{
		$this->manager = $manager;
		return $this;
	}


	/**
	 * Get all the collected scripts and concatenate them.
	 * @return string
	 */
	private function getScriptsAsString(): string
	{
		return array_reduce($this->manager->getScripts(), function($carry, $script) {
			return $carry . $script;
		}, '');
	}


	/**
	 * Print the scripts.
	 */
	public function render()
	{
		echo $this->getScriptsAsString();
	}

}
