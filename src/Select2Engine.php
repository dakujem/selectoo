<?php

namespace Dakujem\Selectoo;

/**
 * Select2Engine
 *
 * This "engine" produces a UI script that controls the Selectoo select input.
 * It is designed for the Select2 library, version 4.0+. But it can be used with other versions as well.
 *
 * For installation instructions, all the options and configuration possibilities, visit the documentation here:
 * @link https://select2.org/ - Select2 v4 documentation
 *
 *
 * Examples:
 *
 * - 3 equal ways to set "allowClear" and "placeholder" options:
 *        $engine->allowClear(true, true)->placeholder('This is my placeholder', true);
 *        $engine->allowClear('"true"')->placeholder('"This is my placeholder"'); // note the quote " characters within the _strings_
 *        $engine->setOption('allowClear', true, true)->setOption('placeholder', 'This is my placeholder', true);
 *
 * - do not escape functions:
 *        $engine->templateSelection('formatState'); // formatState is a JS function
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class Select2Engine implements ScriptEngineInterface
{
    use MagicOptionsTrait;

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
}
