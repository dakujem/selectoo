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
 * Selectoo
 * - hybrid select and multiselect input allowing to attach an engine to generate UI script for the input
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class Selectoo extends BaseControl
{
	/** @var array of option / optgroup */
	protected $options = [];

	/** @var array|null */
	protected $items = null;

	/** @var array */
	protected $itemCallback = null;

	/** @var array */
	protected $optionAttributes = [];

	/** @var mixed */
	protected $prompt = false;

	/**
	 * Is in multi-choice select mode?
	 * In multi-choice mode, values are handled as arrays - setValue expects arrays and arrays are returned from getValue method
	 * @var bool
	 */
	protected $multiChoice = false;

	/** @var bool */
	protected $checkAllowedValues = true;

	/** @var string|null */
	public $defaultCssClass = 'selectoo';

	/** @var ScriptEngineInterface|null */
	protected $engine = null;

	/** @var callable|null */
	protected $scriptManagement = null;


	public function __construct($label = null, $items = null, $multiChoice = false)
	{
		parent::__construct($label);
		if (is_callable($items)) {
			// Note: for some reason, BaseControl calls setValue in constructor... This "reset" is needed for callback loading to work.
			$this->items = null;
			$this->setItemCallback($items);
		} elseif ($items !== null) {
			$this->setItems($items);
		}
		$this->multiChoice = (bool) $multiChoice;
		$this->setOption('type', 'select');
	}


	/**
	 * Returns selected keys.
	 * @return array
	 */
	public function getValue()
	{
		$items = $this->getItems();
		if ($this->isMulti()) {
			return array_values(array_intersect($this->value, array_keys($items)));
		}
		return array_key_exists($this->value, $items) ? $this->value : null;
	}


	/**
	 * Sets selected items (by keys).
	 * @param  array
	 * @return static
	 * @internal
	 */
	public function setValue($value)
	{
		$items = $this->getItems();
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
			if ($this->checkAllowedValues && ($diff = array_diff($value, array_keys($items)))) {
				$set = Strings::truncate(implode(', ', array_map(function ($s) {
											return var_export($s, true);
										}, array_keys($items))), 70, '...');
				$vals = (count($diff) > 1 ? 's' : '') . " '" . implode("', '", $diff) . "'";
				throw new InvalidArgumentException("Value$vals are out of allowed set [$set] in field '{$this->name}'.");
			}
			$this->value = $value;
		} else {
			if ($this->checkAllowedValues && $value !== null && !array_key_exists((string) $value, $items)) {
				$set = Strings::truncate(implode(', ', array_map(function ($s) {
											return var_export($s, true);
										}, array_keys($items))), 70, '...');
				throw new InvalidArgumentException("Value '$value' is out of allowed set [$set] in field '{$this->name}'.");
			}
			$this->value = $value === null ? null : key([(string) $value => null]);
		}
		return $this;
	}


	/**
	 * Sets options and option groups from which to choose.
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
		$this->options = $items;

		$flat = Arrays::flatten($items, true);
		$this->items = $useKeys ? $flat : array_combine($flat, $flat);
		return $this;
	}


	/**
	 * Returns items from which to choose.
	 * @return array
	 */
	public function getItems(): array
	{
		if (!$this->isLoaded()) {
			$this->loadItems();
		}
		return $this->items;
	}


	public function getOptions()
	{
		if (!$this->isLoaded()) {
			$this->loadItems();
		}
		return $this->options;
	}


	protected function isLoaded()
	{
		return $this->items !== null;
	}


	protected function loadItems()
	{
		$callable = $this->getItemCallback();
		$this->setItems($callable !== null ? call_user_func($callable, $this) : []);
	}


	/**
	 * Returns item callback, if set.
	 * @return callable|null
	 */
	public function getItemCallback()
	{
		return $this->itemCallback;
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
	 * Returns selected values.
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
	 * @return bool
	 */
	public function isFilled()
	{
		$v = $this->getValue();
		return $v !== [] && $v !== null;
	}


	/**
	 * Returns selected key (not checked).
	 * @return string|int|array
	 */
	public function getRawValue()
	{
		return $this->value;
	}


	/**
	 * Loads HTTP data.
	 * @return void
	 */
	public function loadHttpData()
	{
		if ($this->isMulti()) {
			$this->value = array_keys(array_flip($this->getHttpData(Form::DATA_TEXT)));
			if (is_array($this->disabled)) {
				$this->value = array_diff($this->value, array_keys($this->disabled));
			}
		} else {
			$this->value = $this->getHttpData(Form::DATA_TEXT);
			if ($this->value !== null) {
				if (is_array($this->disabled) && isset($this->disabled[$this->value])) {
					$this->value = null;
				} else {
					$this->value = key([$this->value => null]);
				}
			}
		}
	}


	/**
	 * @return static
	 */
	public function addOptionAttributes(array $attributes)
	{
		$this->optionAttributes = $attributes + $this->optionAttributes;
		return $this;
	}


	/**
	 * Sets first prompt item in select box.
	 * @param  string|object
	 * @return static
	 */
	public function setPrompt($prompt)
	{
		$this->prompt = $prompt;
		return $this;
	}


	/**
	 * Returns first prompt item?
	 * @return mixed
	 */
	public function getPrompt()
	{
		return $this->prompt;
	}


	public function isMulti(): bool
	{
		return $this->multiChoice;
	}


	/**
	 * Returns HTML name of control.
	 * @return string
	 */
	public function getHtmlName()
	{
		return parent::getHtmlName() . ($this->isMulti() ? '[]' : '');
	}


	/**
	 * Disables or enables control or items.
	 * @param  bool|array
	 * @return static
	 */
	public function setDisabled($value = true)
	{
		if (!is_array($value)) {
			return parent::setDisabled($value);
		}
		parent::setDisabled(false);
		$this->disabled = array_fill_keys($value, true);

		if (!$this->isMulti()) {
			if (isset($this->disabled[$this->value])) {
				$this->value = null;
			}
		} else {
			$this->value = array_diff($this->value, $value);
		}

		return $this;
	}


	/**
	 * Generates control's HTML element.
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
	 * @return Html
	 */
	public function getControlPart()
	{
		$items = $this->prompt === false ? [] : ['' => $this->translate($this->prompt)];
		foreach ($this->getOptions() as $key => $value) {
			$items[is_array($value) ? $this->translate($key) : $key] = $this->translate($value);
		}
		$element = Helpers::createSelectBox(
						$items, [
					'disabled:' => is_array($this->disabled) ? $this->disabled : null,
						] + $this->optionAttributes, $this->value
				)->addAttributes(parent::getControl()->attrs);
		if ($this->isMulti()) {
			$element->multiple(true);
		}
		if ($this->defaultCssClass !== null) {
			$element->class(($element->class ? $element->class . ' ' : '') . $this->defaultCssClass);
		}
//		$element->value($this->getValue()); //TODO needed ??
		return $element;
	}


	public function getScriptPart()
	{
		$content = $this->getEngine() !== null ? $this->getEngine()->getUiScript($this) : null;
		return $content !== null ? Html::el('script')->type('text/javascript')->setHtml((string) $content) : null;
	}


	/**
	 * Set Selectoo engine.
	 *
	 *
	 * @param ScriptEngineInterface|string|callable|null $engine engine instance, factory or class name
	 * @return $this
	 */
	public function setEngine($engine)
	{
		$this->engine = $engine;
		return $this;
	}


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


	public function getScriptManagement()
	{
		return $this->scriptManagement;
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
	 *
	 * @param callable $routine  a function with signature   function($script, $htmlElement, $selectooInput): string|null
	 * @return self
	 */
	public function setScriptManagement(callable $routine = null)
	{
		$this->scriptManagement = $routine;
		return $this;
	}


	public function __clone()
	{
		parent::__clone();
		if ($this->engine !== null) {
			$this->engine = clone $this->engine;
		}
	}

}
