<?php


namespace Dakujem\Selectoo;

use BadMethodCallException,
	Nette\Forms\Controls\BaseControl,
	Nette\Forms\Form,
	Nette\Forms\Helpers,
	Nette\InvalidArgumentException,
	Nette\InvalidStateException,
	Nette\Utils\Arrays,
	Nette\Utils\Html,
	Nette\Utils\Strings;


/**
 * Selectoo - flexible select input
 *
 * - hybrid select and multiselect input
 * - allows attachment of "engines" to generate customized UI scripts for the input
 * - encourages usage of factories to create reusable application select inputs
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class Selectoo extends BaseControl
{
	/**
	 * Is in multi-choice select mode?
	 * In multi-choice mode, values are handled as arrays - setValue expects arrays and arrays are returned from getValue method
	 * @var bool
	 */
	protected $multiChoice = false;

	/**
	 * Array of possible values of the input
	 * @var array|null
	 */
	protected $items = null;

	/**
	 * Callback used for lazy-loading (on demand) of items.
	 * The use of callback allows for dynamic option loading using ajax.
	 * @var array
	 */
	protected $itemCallback = null;

	/**
	 * Array of child elements that represent HTML tags - option / optgroup
	 * @var array
	 */
	protected $elements = [];

	/**
	 * Attributes for option elements.
	 * @var array
	 */
	protected $optionAttributes = [];

	/** @var mixed */
	protected $prompt = false;

	/** @var bool */
	protected $validateValuesOnSet = false;

	/** @var string|null */
	protected $defaultCssClass = 'selectoo';

	/** @var ScriptEngineInterface|null */
	protected $engine = null;

	/** @var callable|null */
	protected $scriptManagement = null;


	/**
	 * Selectoo - flexible select input.
	 *
	 * @param string|null $label
	 * @param array|callable|null $items pass a callable for lazy options loading
	 * @param bool $multiChoice the mode of operation of the input
	 */
	public function __construct($label = null, $items = null, $multiChoice = false)
	{
		$this->multiChoice = (bool) $multiChoice;
		parent::__construct($label);
		if (is_callable($items)) {
			$this->setItemCallback($items);
			// Note: for some reason, BaseControl calls setValue in constructor... This "reset" is needed for callback loading to work.
			$this->unload();
		} elseif ($items !== null) {
			$this->setItems($items);
			$this->validateValuesOnSet(true); // when fixed array of items is provided, validate by default
		}
		$this->setOption('type', 'select');
	}


	/**
	 * Is the Selectoo input in multi-choice mode?
	 *
	 * In multi-choice mode,  array  values are expected and returned.
	 * In single-choice mode, scalar values are expected and returned.
	 *
	 * @return bool true when in multi-choice mode
	 */
	public function isMulti(): bool
	{
		return $this->multiChoice;
	}


	/**
	 * Sets selected items (by keys).
	 *
	 * Note: it is preferable to use setDefaultValue to set initial value of the input instead
	 *
	 * @param string|int|array $value a scalar value in single-choice mode, an array in multi-choice mode
	 * @return self
	 */
	public function setValue($value)
	{
		if ($this->isMulti()) {
			if (is_scalar($value) || $value === null) {
				$value = (array) $value;
			} elseif (!is_array($value)) {
				throw new InvalidArgumentException(sprintf("Value must be array or null, %s given in field '%s'.", gettype($value), $this->name));
			}
			$flip = [];
			foreach ($value as $single) {
				if (!is_scalar($single) && !method_exists($single, '__toString')) {
					throw new InvalidArgumentException(sprintf("Values must be scalar, %s given in field '%s'.", gettype($single), $this->name));
				}
				$flip[(string) $single] = true;
			}
			$value = array_keys($flip);
			$this->value = $value;
		} else {
			$this->value = $value === null ? null : key([(string) $value => null]);
		}
		if ($this->validateValuesOnSet && $this->value !== [] && $this->value !== null) {
			$this->validateValueInOptions($this->value);
		}
		return $this;
	}


	/**
	 * Returns selected key or keys, depending.
	 *
	 * @return string|int|array returns array in multi-choice mode, int or string in single-choice mode
	 */
	public function getValue()
	{
		$items = $this->getItems();
		$disabled = $this->getDisabled();
		if ($this->isMulti()) {
			$val = array_values(array_intersect($this->value, array_keys($items)));
			return is_array($disabled) ? array_diff($val, array_keys($disabled)) : $val;
		}
		$val = array_key_exists($this->value, $items) ? $this->value : null;
		return isset($disabled[$val]) ? NULL : $val;
	}


	/**
	 * Returns selected key (not checked).
	 *
	 * Note that the result of this call is NOT SAFE to use because the input can have ANY raw value!
	 *
	 * @return string|int|array returns array in multi-choice mode, int or string on single-choice mode
	 */
	public function getRawValue()
	{
		return $this->value;
	}


	/**
	 * Sets options and option groups from which to choose.
	 *
	 * @return static
	 */
	public function setItems(array $items, $useKeys = true)
	{
		if (!$useKeys) {
			$res = [];
			foreach ($items as $key => $value) {
				unset($items[$key]);
				if (is_array($value)) {
					foreach ($value as $val) {
						$res[$key][(string) $val] = $val;
					}
				} else {
					$res[(string) $value] = $value;
				}
			}
			$items = $res;
		}
		$this->elements = $items;

		$flat = Arrays::flatten($items, true);
		$this->items = $useKeys ? $flat : array_combine($flat, $flat);
		return $this;
	}


	/**
	 * Get an array of all possible values that represent the items from which to choose.
	 *
	 * @return array
	 */
	public function getItems(): array
	{
		!$this->isLoaded() && $this->reload();
		return $this->items;
	}


	/**
	 * Get an array of structured child elements that represent HTML tags - option / optgroup
	 *
	 * @return array
	 */
	public function getElements(): array
	{
		!$this->isLoaded() && $this->reload();
		return $this->elements;
	}


	/**
	 * Set item callback.
	 * The callback will be used to retrieve items when needed.
	 *
	 * @param callable|null $itemCallback
	 * @return self
	 */
	public function setItemCallback(callable $itemCallback = null)
	{
		$this->itemCallback = $itemCallback;
		return $this;
	}


	/**
	 * Returns item callback, if set.
	 *
	 * @return callable|null
	 */
	public function getItemCallback()
	{
		return $this->itemCallback;
	}


	/**
	 * Returns selected values.
	 *
	 * @return array
	 */
	public function getSelectedItems(): array
	{
		$value = $this->getValue();
		if ($value === null) {
			return [];
		}
		$items = $this->getItems();
		if (is_scalar($value)) {
			$value = [$value];
		}
		return array_intersect_key($items, array_flip($value));
	}


	/**
	 * Returns selected value.
	 * Single-choice mode only.
	 *
	 * @return mixed
	 */
	public function getSelectedItem()
	{
		if ($this->isMulti()) {
			throw new BadMethodCallException('Cannot call method getSelectedItem in multi-choice mode. Call getSelectedItems instead.');
		}
		$value = $this->getValue();
		return $value === null ? null : ($this->getItems()[$value] ?? null);
	}


	/**
	 * Is any item selected?
	 *
	 * @return bool
	 */
	public function isFilled():bool
	{
		$v = $this->getValue();
		return $v !== [] && $v !== null;
	}


	/**
	 * Sets first prompt item in select box.
	 *
	 * @param  string|object
	 * @return self
	 */
	public function setPrompt($prompt)
	{
		$this->prompt = $prompt;
		return $this;
	}


	/**
	 * Returns first prompt item?
	 *
	 * @return mixed
	 */
	public function getPrompt()
	{
		return $this->prompt;
	}


	/**
	 * Returns HTML name of control.
	 *
	 * @return string
	 */
	public function getHtmlName():string
	{
		return parent::getHtmlName() . ($this->isMulti() ? '[]' : '');
	}


	/**
	 * Disables or enables control or items.
	 *
	 * @param  bool|array
	 * @return self
	 */
	public function setDisabled($value = true)
	{
		if (!is_array($value)) {
			return parent::setDisabled($value);
		}
		parent::setDisabled(false);
		$this->disabled = array_fill_keys($value, true);
		return $this;
	}


	/**
	 * Discover whether the input is disabled, enabled or partially disabled.
	 *
	 * @return bool|array bool indicates that the whole input is/is not disabled; array indicates disabled values (partially disabled input)
	 */
	public function getDisabled()
	{
		return $this->disabled;
	}


	/**
	 * Generates control's HTML element.
	 *
	 * @return Html
	 */
	public function getControl()
	{
		$element = $this->getControlPart();
		$script = $this->getScriptPart();
		if ($script !== null && $this->getScriptManagement() !== null) {
			$script = call_user_func($this->getScriptManagement(), $script, $element, $this);
		}
		if ($script !== null) {
			$wrapper = WrapperHtml::el();
			$wrapper[] = $script;
			$wrapper[] = $element;
			$wrapper->forwardTo($element);
			return $wrapper;
		}
		return $element;
	}


	/**
	 * Return the select part (tag) of the Selectoo input.
	 *
	 * @return Html
	 */
	public function getControlPart(): ?Html
	{
		$prompt = $this->getPrompt();
		$items = $prompt === false ? [] : ['' => $this->translate($prompt)];
		foreach ($this->getElements() as $key => $value) {
			$items[is_array($value) ? $this->translate($key) : $key] = $this->translate($value);
		}
		$disabled = $this->getDisabled();
		$parentAttributes = parent::getControl()->attrs;
		$optionAttributes = [
			'disabled:' => is_array($disabled) ? $disabled : null,
				] + $this->optionAttributes;
		$element = Helpers::createSelectBox($items, $optionAttributes, $this->getRawValue())->addAttributes($parentAttributes);
		if ($this->isMulti()) {
			$element->multiple(true);
		}
		if ($this->getDefaultCssClass() !== null) {
			$element->class(($element->class ? $element->class . ' ' : '') . $this->getDefaultCssClass());
		}
		return $element;
	}


	/**
	 * Return the UI script part (tag) of the Selectoo input.
	 *
	 * @return Html|null
	 */
	public function getScriptPart()
	{
		$content = $this->getEngine() !== null ? $this->getEngine()->getUiScript($this) : null;
		return $content !== null ? Html::el('script')->type('text/javascript')->setHtml((string) $content) : null;
	}


	/**
	 * Set Selectoo UI script engine.
	 *
	 * @param ScriptEngineInterface|string|callable|null $engine engine instance, factory or class name
	 * @return self
	 */
	public function setEngine($engine)
	{
		$this->engine = $engine;
		return $this;
	}


	/**
	 * Get assigned Selectoo UI script engine instance.
	 *
	 * @return ScriptEngineInterface|null
	 * @throws InvalidStateException
	 */
	public function getEngine()
	{
		if (!$this->engine instanceof ScriptEngineInterface && is_callable($this->engine)) {
			$this->engine = call_user_func($this->engine, $this);
			return $this->getEngine();
		}
		if (is_string($this->engine)) {
			$className = $this->engine;
			$this->engine = new $className($this);
			return $this->getEngine();
		}
		if ($this->engine !== null && !$this->engine instanceof ScriptEngineInterface) {
			throw new InvalidStateException(sprintf('Invalid engine has been set. An instance of %s or the interface-implementing-class-name string or a factory returning those must be set.', ScriptEngineInterface::class));
		}
		return $this->engine;
	}


	/**
	 * Set a script management routine.
	 *
	 * The routine is called after the control's UI script has been generated and can alter the script in any way.
	 * The return value of the routine replaces the original generated script.
	 * In case null is returned by the routine, the script will NOT be present in the result of getControl() call!
	 *
	 * This feature can be used for example to gather all scripts generated by inputs and other components
	 * and then to print them out at the end of the HTML document. The routine should return null in such use cases.
	 *
	 * @param callable|null $routine  a function with signature   function($script, $htmlElement, $selectooInput): string|null
	 * @return self
	 */
	public function setScriptManagement(callable $routine = null)
	{
		$this->scriptManagement = $routine;
		return $this;
	}


	/**
	 * Return assigned script management routine.
	 *
	 * @return callable|null
	 */
	public function getScriptManagement()
	{
		return $this->scriptManagement;
	}


	/**
	 * Add attributes for option HTML tags.
	 *
	 * @return self
	 */
	public function addOptionAttributes(array $attributes)
	{
		$this->optionAttributes = $attributes + $this->optionAttributes;
		return $this;
	}


	/**
	 * Set attributes for option HTML tags.
	 *
	 * @return self
	 */
	public function setOptionAttributes(array $attributes)
	{
		$this->optionAttributes = $attributes;
		return $this;
	}


	/**
	 * Get attributes for option HTML tags.
	 *
	 * @return array
	 */
	public function getOptionAttributes(): array
	{
		return $this->optionAttributes;
	}


	/**
	 * Set the default CSS class that is added to the base element.
	 *
	 * @param string|null $defaultCssClass
	 * @return self
	 */
	public function setDefaultCssClass($defaultCssClass)
	{
		$this->defaultCssClass = $defaultCssClass;
		return $this;
	}


	/**
	 * Get the default CSS class that is added to the base element.
	 *
	 * @return string|null
	 */
	public function getDefaultCssClass()
	{
		return $this->defaultCssClass;
	}


	/**
	 * Set flag whether to validate the value when calling setValue.
	 * When setting a value that is not within the possible options an exception will be thrown.
	 *
	 * Note that validating during setValue calls has the side effect of loading items.
	 * That may cause problems when using dependent inputs because the other inputs might not be loaded yet.
	 *
	 * @param bool $validate
	 * @return self
	 */
	public function validateValuesOnSet(bool $validate = true)
	{
		$this->validateValuesOnSet = $validate;
		return $this;
	}


	/**
	 * Validates that every selected option (value) is in the array of possible options (items).
	 *
	 * @param int|string|array $value
	 * @return self
	 * @throws InvalidArgumentException
	 */
	protected function validateValueInOptions($value)
	{
		$items = $this->getItems();
		if ($this->isMulti()) {
			$diff = array_diff($value, array_keys($items));
			if ($diff) {
				$set = Strings::truncate(implode(', ', array_map(function ($s) {
											return var_export($s, true);
										}, array_keys($items))), 70, '...');
				$vals = (count($diff) > 1 ? 's' : '') . " '" . implode("', '", $diff) . "'";
				throw new InvalidArgumentException("Value$vals are out of allowed set [$set] in field '{$this->name}'.");
			}
		} else {
			if ($value !== null && !array_key_exists((string) $value, $items)) {
				$set = Strings::truncate(implode(', ', array_map(function ($s) {
											return var_export($s, true);
										}, array_keys($items))), 70, '...');
				throw new InvalidArgumentException("Value '$value' is out of allowed set [$set] in field '{$this->name}'.");
			}
		}
		return $this;
	}


	/**
	 * Have the items/elements been loaded?
	 *
	 * @return bool
	 */
	protected function isLoaded(): bool
	{
		return $this->items !== null;
	}


	/**
	 * Unload dynamically loaded items. Use this method to force reloading the items.
	 *
	 * Calling this method only makes sense when item callback is set. Has no effect otherwise.
	 *
	 * @return self
	 */
	public function unload()
	{
		if ($this->getItemCallback() !== null) {
			$this->items = null;
		}
		return $this;
	}


	/**
	 * Load dynamic items and elements.
	 * Note that when no callback is set, it resets items to empty array.
	 *
	 * @return self
	 */
	protected function reload()
	{
		$callable = $this->getItemCallback();
		$this->setItems($callable !== null ? call_user_func($callable, $this->getRawValue(), $this) : []);
		return $this;
	}


	/**
	 * Will the items be loaded later when needed?
	 *
	 * @return bool
	 */
	protected function isDormant(): bool
	{
		return !$this->isLoaded() && $this->getItemCallback() !== null;
	}


	/**
	 * Loads HTTP data.
	 *
	 * @return void
	 */
	public function loadHttpData():void
	{
		if ($this->isMulti()) {
			$this->value = array_keys(array_flip($this->getHttpData(Form::DATA_TEXT)));
		} else {
			$raw = $this->getHttpData(Form::DATA_TEXT);
			if ($raw !== null) {
				$this->value = key(array($raw => null)); // this lousy trick converts numbers to integer, other values to string
			}
		}
	}


	/**
	 * Performs deep cloning - engine gets cloned as well.
	 */
	public function __clone()
	{
		parent::__clone();
		if ($this->engine !== null) {
			$this->engine = clone $this->engine;
		}
	}

}
