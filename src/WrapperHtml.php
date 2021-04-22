<?php

declare(strict_types=1);

namespace Dakujem\Selectoo;

use Nette\Utils\Html as NetteHtml;

/**
 * Simple Html extension
 * that solves the problem with $input->getControl() returning an input wrapped in another Html instance
 * and not directly the input/select tag
 * while using the Nette form macros, namely {input ...} macro.
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class WrapperHtml extends NetteHtml
{
    /** @var array */
    protected $forwardedElements = [];

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
