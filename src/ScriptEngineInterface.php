<?php


namespace Dakujem\Selectoo;


/**
 * Selectoo UI Script Engine Interface
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
interface ScriptEngineInterface
{


	/**
	 * Returns user interface script that controls the selectoo input.
	 *
	 *
	 * @param mixed $control the instance of the Selectoo control
	 * @return string|null   anything that can be type cast to string
	 */
	function getUiScript($control);

}
