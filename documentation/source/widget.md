# Widget Execution

## Widget execution model

The follow pseudocode outlines how widgets are executed. 

	request
	if (no model) {
		model
		defaults
	}
	initialize
	if (submitted) {
		load
		if (validate) {
			submit
		}
	}
	render
	
Each phase is outlined below.

## request

	/**
	 * @return Request 
	 */
    function request($set=null)

Return the current request, and do any pre-check steps on the request, if needed.

## model

	/**
	 * @return Model 
	 */
    function model()

Create the model if (and only if) it is not supplied by the `execute` method. Generally this is called for the top-level widget in a widget tree. After this is created, the defaults method is called to populate it.

## defaults

	/**
	 * @return array
	 */
	function defaults()

When a form is initially displayed, the `defaults` method will populate the blank `Model` with default values.

## initialize

	/**
	 * @return void
	 */
	function initialize()

This function is always called before all other functions (submitted/load/validate/submit/defaults/render), the `initialize` method sets up any widgets or other values for a widget prior to processing.

## submitted

	/**
	 * @return boolean
	 */
	function submitted(Model $model)

Determine if the widget was submitted as part of a form. Returns true or false. Generally should 
check the request to see if it was *posted* (`$this->request->isPost()`) or if a value exists
as part of the query string. 

## load

	/**
	 * @return void
	 */
	function load(Model $model)

Populate the model with the values from the request. load should avoid doing any form of 
validation of the values, and leave this step to the `validate` phase.

## validate

	/**
	 * @return boolean
	 */
	function validate(Model $model)

Check values as populated by the load step to determine if they are valid or not. Such things as
valid select items, correct formatting, etc. should be determined here. Note that validation *must*
be performed even in values are validated on the client-side. Return `true` if valid, `false` if
invalid.

## submit

	/**
	 * @return void
	 */
	function submit(Model $model)

Save, store values, or complete the form submission. May cause a redirect (`$this->response->redirect($url)`), or
may simply continue onto the render stage to output the form again.

## render

	/**
	 * @return string
	 */
	function render(Model $model)
	
Display the widget on the form.
