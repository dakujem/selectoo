<?php


namespace Dakujem\Selectoo;

use Nette\Utils\Html as NetteHtml;


class WrapperHtml extends NetteHtml
{
	/** @var array */
	private $forwardedElements = [];


	public function forwardTo(NetteHtml $element)
	{
		$this->forwardedElements[] = $element;
		return $this;
	}


	public function getForwardedElements()
	{
		return $this->forwardedElements;
	}


	public function clearForwardedElements()
	{
		$this->forwardedElements = [];
		return $this;
	}


	public function addAttributes(array $attrs)
	{
		foreach ($this->getForwardedElements() as $element) {
			$element->addAttributes($attrs);
		}
		return parent::addAttributes($attrs);
	}

}
