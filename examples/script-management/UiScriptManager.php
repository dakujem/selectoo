<?php


namespace Dakujem\Selectoo\Examples;


/**
 * UiScriptManager - trivial example of script collector service.
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
class Dkj_UiScriptManager
{
	private $scripts = [];


	public function addScript($script)
	{
		$this->scripts [] = $script;
		return $this;
	}


	public function getScripts(): array
	{
		return $this->scripts;
	}

}
