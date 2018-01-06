<?php


namespace Dakujem\Selectoo;


/**
 * Select2Engine
 *
 * This "engine" produces a UI script that controls the Selectoo select input.
 * It uses the Select2 v4 library.
 *
 * For all the options and configuration possibilities, visit the documentation here:
 * @link https://select2.org/ Select2 v4 documentation.
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class Select2Engine implements ScriptEngineInterface
{
	/**
	 * All the options are strings or callables in case you want a callable string, use \ as the first character.
	 * 
	 * @var string[]|callable[]
	 */
	protected $options = [];


	/**
	 * Returns Select2 configuration options.
	 *
	 *
	 * @return string
	 */
	protected function getOptionsAsString(): string
	{
		$compiled = [];
		foreach ($this->options as $key => $opt) {
			$compiled[] = $key . ': ' . (!is_callable($opt) || (is_string($opt) && $opt[0] !== '\\') ? $opt : call_user_func($opt, $key, $this));
		}
		return count($compiled) > 0 ? '{' . implode(', ', $compiled) . '}' : '';
	}


	protected function selector($control)
	{
		return '$(\'#' . $control->getHtmlId() . '\')';
	}


	public function getUiScript($control)
	{
		$js = '
			(function($){
				$(document).ready(function(){
					' . $this->selector($control) . '.select2(' . $this->getOptionsAsString() . ');
				});
			})(jQuery);
		';
		return $js;
	}


	public function setOption($name, $value, $toJs = false)
	{
		if ($toJs) {
			if ($value === null) {
				$value = 'null';
			} elseif (is_bool($value)) {
				$value = $value ? 'true' : 'false';
			} elseif (is_numeric($value)) {
				// nothing changes
			} else {
				$value = '"' . $value . '"';
			}
		}
		$this->options[$name] = $value;
		return $this;
	}


	public function getOption($name)
	{
		return $this->options[$name] ?? null;
	}


	public function unsetOption($name)
	{
		unset($this->options[$name]);
		return $this;
	}


	public function __call($name, $arguments)
	{
		if (count($arguments) > 0) {
			return $this->setOption($name, $arguments[0], $arguments[1] ?? false);
		}
		if (substr($name, 0, 5) === 'unset') {
			return $this->unsetOption(lcfirst(substr($name, 5)));
		}
		return $this->getOption($name);
	}


	public function __get($name)
	{
		return $this->getOption($name);
	}


	public function __set($name, $value)
	{
		return $this->setOption($name, $value);
	}

}
