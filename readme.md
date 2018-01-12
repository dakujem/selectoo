# Selectoo

SelectBox & MultiSelectBox hybrid input with lazy & asynchronous options loading capabilities to be used with Select2, Selectize, Chosen and similar UI libraries.

The aim of Slectoo is to provide a flexible tool for creating reusable select inputs.


## Features

- can work in single-choice or multi-choice modes
	- single-choice mode is roughly equal to `SelectBox`
	- multi-choice  mode is roughly equal to `MultiSelectBox`
- on-demand (lazy) loading of options using a callback
	- options can be loaded asynchronously using AJAX
	- options are only loaded when really needed
- can be skinned with UI libraries or custom scripts (see below)
- dependent / cascading inputs


## Notable differences compared to Nette SelectBox & MultiSelectBox

- lazy options loading using a callback
- disabling a Selectoo input does not modify/reset its value, so it can be re-enabled without the loss of the information


## Lazy options loading

Lazy loading is used when the Selectoo instance is given "item callback" callable instead of an array of items.
The callable is supposed to return a list of items.

The item callback also works as a validator for values.
It receives raw value as the first argument (and the input instance as the second).
The callback should return the array of possible values (these are the items) in form
exactly the same as the items set to `SelectBox` or `MultiSelectBox` inputs.
The raw value is then compared to the returned items and filtered.
This approach ensures the validity of the data.

It is important to set this callback in case you work with remote item loading (ajax/api loading of options).


## Supported and intended UI libraries

Selectoo uses "script engines" to generate JavaScript UI scripts along with HTML input element.
Any script engine can be attached to the `Selectoo` instance by calling `$selectoo->setEngine($engine)`.

- [select2/select2](https://github.com/select2/select2)
	- use the `Select2Engine` instances
- selectize/selectize.js
	- to be implemented
- harvesthq/chosen
	- to be implemented

Custom engines can easily be created implementing the `ScriptEngineInterface` interface.


## Usage / Use cases

**Reusable** inputs with specific setup:
- select2-skinned (or other library) input with special configuration options and other application logic
- autocomplete with ajax
- tagging
- custom ui script for altering the DOM upon selection
- cascading / dependent inputs


## Factories

For best reusability I strongly encourage using factories for inputs with more logic,
typically these involve fetching data from storage or external resources along with JS/UI configuration,
handling events and value validation.

See simple examples:
- [general selectoo factory](examples/factories/Select2SelectooFactory.php)
- [example user select factory](examples/factories/UserSelectooFactory.php)
- [example AJAX API loaded input](examples/ajax/UserAjaxSelectooFactory.php)


## Lazy loading / AJAX

See the [example ajax presenter](examples/ajax/ExamplePresenter.php),
complete with input factory and user repository examples.


## Dependent selections / cascading inputs

Example is being prepared.

What is needed, in general:
- UI script that manages on change events
- Select2 configuration that sends the value of master inputs in the URL of the AJAX request
- repository filtering based upon the values
- API endpoint that receives the master values and makes repository request, using values from the URL
- "item callback" that also makes repository request, using values from `Form`

> Note that when the item callback is called the values from the `Form` object can be used to check for master values

> By "master values" above I mean the values of the inputs the Selectoo instance depends on

By following these steps, dependent Selectoo inputs can be created and values validated.


## Requirements

- PHP 7+
- `nette/forms` v `2.4` and above

You also need **Select2** version `4` to use the `Select2Engine`, see [Select2 documentation](https://select2.org/).


