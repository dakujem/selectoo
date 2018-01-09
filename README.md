# Selectoo


- lazy (callback) item loading
- disabling a Selectoo input does not modify/reset its value, so it can be re-enabled without the loss of the information


The item callback also works as a validator for values. It receives raw value as the first argument.
The callback should return the array of possible values (items). The raw value is then compared to the possible options and filtered.
It is important to set this callback in case you work with remote item loading (ajax/api loading of options).


Ui libraries
- [select2/select2](https://github.com/select2/select2)
- selectize/selectize.js
- harvesthq/chosen




Of course, you can build your own engines.

