<?php

namespace Dakujem\Selectoo;

/**
 * MagicOptionsTrait
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
trait MagicOptionsTrait
{
    /**
     * All the options should be strings or callables;
     * in case you want a callable string, use "\" as the first character.
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

    /**
     * Set an option.
     *
     * Note the last parameter - when set to true, the $value is "cast" into a JS type.
     *
     *
     * @param string $name name of the option - see library API documentation
     * @param mixed $value the value to set the option to
     * @param bool $toJs set to true to sort of cast the $value to JS type
     * @return $this
     */
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

    /**
     * Enables magic setter calls in the following fashion:
     * $engine->foo(4)->bar(true, true);
     *
     * When no arguments are passed, it behaves as a getter:
     * $engine->foo(); // 4
     *
     * Unsetting options is also possible:
     * $engine->unsetFoo();
     *
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
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
