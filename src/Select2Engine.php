<?php


namespace Dakujem\Selectoo;


/**
 * Select2Engine
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class Select2Engine implements ScriptEngineInterface
{


	/**
	 * Returns Select2 configuration options.
	 *
	 *
	 * @return string
	 */
	protected function getOptionsAsString(): string
	{
		return '';
	}


	public function getUiScript($control)
	{
		$js = '
			(function($){
				$(document).ready(function(){
					$(\'#' . $control->getHtmlId() . '\').select2(' . $this->getOptionsAsString() . ');
				});
			})(jQuery);
		';
		return $js;
	}

}
