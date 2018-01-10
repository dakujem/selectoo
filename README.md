# Selectoo

SelectBox & MultiSelectBox hybrid input with lazy & asynchronous options loading capabilities to be used with Select2, Selectize, Chosen and similar UI libraries.


## Features

- can work in single-choice or multi-choice modes
	- single-choice mode is roughly equal to `SelectBox`
	- multi-choice mode is roughly equal to `MultiSelectBox`
- on-demand (lazy) loading of options using a callback
	- options can be loaded asynchronously using AJAX
	- options are only loaded when really needed
- can be skinned with other UI libraries or custom scripts


## Differences compared to Nette SelectBox & MultiSelectBox

- lazy (callback) item loading
- disabling a Selectoo input does not modify/reset its value, so it can be re-enabled without the loss of the information


## Lazy options loading

Lazy loading is used when the Selectoo instance is given "item callback" callable instead of an array of items.

The item callback also works as the validator for values. It receives raw value as the first argument (and the input instance as the second).
The callback should return the array of possible values (items). The raw value is then compared to the possible options and filtered.
It is important to set this callback in case you work with remote item loading (ajax/api loading of options).


## Supported and intended UI libraries

- [select2/select2](https://github.com/select2/select2)
- selectize/selectize.js
- harvesthq/chosen

Custom engines can be easily created implementing the `ScriptEngineInterface` interface.


## Requirements

- PHP 7+
- `nette/forms` v `2.4` and above
