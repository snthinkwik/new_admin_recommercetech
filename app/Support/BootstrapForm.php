<?php namespace App\Support;

use Doctrine\Common\Inflector\Inflector;
use Exception;
use Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Str;

/**
 * This is a wrapper around the regular Form facade with extra functionality helpful in Bootstrap.
 *
 * General idea of this class is to have use methods as similar as possible to Form's methods. If we need extra parameters
 * then we add an extra parameter at the end. For instance Form:model() has 2 parameters, we add 1 at the end.
 *
 * The simplest thing that this class does is add default classes to form elements. So you don't have to write
 * `Form::text('name', null, ['class' => 'form-control'])
 * instead you write
 * `BsForm::text('name')
 *
 * A slightly more advanced functionality is automatically creating form groups and labels. If you form group has a
 * standard structure, you can use a method prefixed with "group", for instance `BsForm::groupText`. That will create
 * a text input in an form group with label. See the documentation for group() method for more details.
 */
class BootstrapForm
{
	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @param $model The same as in Form
	 * @param array $options The same as in Form
	 * @param array $extraOptions ['id-prefix'] Prefix that will be added to all ids inside the form.
	 * @return mixed
	 */
	public function model($model, array $options = [], array $extraOptions = [])
	{
		$this->model = $model;
		return $this->open($options, $extraOptions);
	}

	/**
	 * @param array $options The same as in Form
	 * @param array $extraOptions ['id-prefix'] Prefix that will be added to all ids inside the form.
	 * @return mixed
	 */
	public function open(array $options = [], array $extraOptions = [])
	{
		$this->options = $extraOptions;
		if (isset($this->options['id-and-prefix'])) {
			$this->options['id-prefix'] = $this->options['id-and-prefix'];
			$options['id'] = $this->options['id-and-prefix'];
		}

		return $this->model
			? Form::model($this->model, $options)
			: Form::open($options);
	}

	public function close()
	{
		$this->options = null;
		$this->model = null;
		return Form::close();
	}

	public function file($name, $options = array())
	{
		return Form::file($name, $options);
	}

	public function submit($value = null, $options = [])
	{
		$this->appendValue($options, 'class', 'btn btn-primary', 'btn ');

		return Form::submit($value, $options);
	}

	public function button($value = null, $options = [])
	{
		$this->appendValue($options, 'class', 'btn btn-primary', 'btn ');

		return Form::button($value, $options);
	}

	public function textarea($name, $value = null, $options = [])
	{
		$this->appendValue($options, 'class', 'form-control');

		return Form::textarea($name, $value, $options);
	}

	public function hidden($name, $value = null, $options = [])
	{
		return Form::hidden($name, $value, $options);
	}

	public function checkbox($name, $value = 1, $checked = null, $options = [], $label = null)
	{
		$html = Form::checkbox($name, $value, $checked, $options);
		if ($label) {
			$html = "<div class=\"checkbox\"><label>$html $label</label></div>";
		}
		return $html;
	}

	public function radio($name, $value = 1, $checked = null, $options = [], $label = null)
	{
		$html = Form::radio($name, $value, $checked, $options);
		if ($label) {
			$html = "<div class=\"radio\"><label>$html $label</label></div>";
		}
		return $html;
	}

	public function text($name, $value = null, $options = [])
	{
		$this->appendValue($options, 'class', 'form-control');

		return Form::text($name, $value, $options);
	}

	public function email($name, $value = null, $options = [])
	{
		$this->appendValue($options, 'class', 'form-control');

		return Form::email($name, $value, $options);
	}

	public function select($name, $list = [], $selected = null, $options = [])
	{
		$this->appendValue($options, 'class', 'form-control');

		return Form::select($name, $list, $selected, $options);
	}

	public function number($name, $value = null, $options = [])
	{
		$this->appendValue($options, 'class', 'form-control');

		return Form::number($name, $value, $options);
	}

	/**
	 * Used to add classes to the $options array etc.
	 * @param $array
	 * @param $propName
	 * @param $text
	 * @param string $unlessExists Only add value if this string doesn't exist in it.
	 */
	protected function appendValue(&$array, $propName, $text, $unlessExists = '')
	{
		if ($unlessExists && isset($array[$propName]) && strpos($array[$propName], $unlessExists) !== false) {
			return;
		}

		if (!isset($array[$propName])) {
			$array[$propName] = $text;
		}
		elseif (strpos($array[$propName], $text) === false) {
			$array[$propName] .= ' ' . $text;
		}
	}

	/**
	 * Create a form element with the form-group div and label.
	 *
	 * @param string $type You won't be calling this method directly. Instead you'll call BsForm::groupSelect,
	 *                     BsForm::groupText etc. The "group" part will be removed and the remaining part will be
	 *                     used to call the method for the desired form element type, e.g. BsForm::select or BsForm::text.
	 *
	 * @param $arguments The arguments will vary depending on the form element you want. But for each element you can
	 *                   add one more array at the end - that will be the options for BsForm. The available options are:
	 *                   string|bool lastArgument['label'] Set it to false if you don't want the label.
	 *                                                     Set it to true if you want a label for an element that wouldn't
	 *                                                     normally have it, but you want the automatic label name.
	 *                                                     Set it to a string if you want a specific label.
	 * @return string
	 */
	protected function group($type, $arguments)
	{
		$optionsIdx = 2;      // On which index of $arguments the options for Form are.
		$extraOptionsIdx = 3; // On which index of $arguments the options for BsForm
		$hasLabel = true;     // By default, we assume all inputs have labels.
		$errors = session('errors') ?: new ViewErrorBag();
		$elementName = $arguments[0];
		$isCheckboxRadio = in_array($type, ['checkbox', 'radio']);

		if (in_array($type, ['submit', 'file'])) {
			$optionsIdx = 1;
			$extraOptionsIdx = 2;
			$hasLabel = false;
		}
		elseif (in_array($type, ['select', 'checkbox', 'radio'])) {
			$optionsIdx = 3;
			$extraOptionsIdx = 4;
		}

		$extraOptions = isset($arguments[$extraOptionsIdx]) ? $arguments[$extraOptionsIdx] : [];
		if (isset($arguments[$extraOptionsIdx])) unset($arguments[$extraOptionsIdx]);

		if (!empty($extraOptions['errors_name'])) {
			$errorIdx = $extraOptions['errors_name'];
		}
		else {
			$errorIdx = str_replace(['[', ']'], ['.', ''], $elementName); // Index for this element in the error array.
		}

		// Fill the empty $arguments with nulls. Without it we might have problems later if for instance $arguments[0]
		// and $arguments[2] are defined, but $arguments[1] isn't.
		foreach (range(0, $optionsIdx) as $i) {
			if (!isset($arguments[$i])) {
				$arguments[$i] = null;
			}
		}

		// Label turned of in options.
		if (isset($extraOptions['label']) && $extraOptions['label'] === false) {
			$hasLabel = false;
		}
		// Label overridden in options. Even available for inputs that don't normally have labels.
		elseif (!empty($extraOptions['label'])) {
			$hasLabel = true;
		}

		$id = null;
		// Determine the id of the input, if available.
		if (!empty($arguments[$optionsIdx]['id'])) {
			$id = $arguments[$optionsIdx]['id'];
		}
		if (!empty($this->options['id-prefix'])) {
			$id = $this->options['id-prefix'] . '-' . ($id ?: Str::limit($elementName));
		}

		if ($id) {
			if (!isset($arguments[$optionsIdx])) $arguments[$optionsIdx] = [];
			$arguments[$optionsIdx]['id'] = $id;
		}

		$groupParts = ['<div class="form-group ' . ($errors->has($errorIdx) ? 'has-error' : '') . '">'];
		if ($hasLabel) {
			if ($isCheckboxRadio) {
				$groupParts[] = '<div class="' . e($type) . '">';
			}
			$label = !empty($extraOptions['label']) && $extraOptions['label'] !== true
				? $extraOptions['label']
				: ucwords(str_replace('_', ' ', $elementName));

			$groupParts[] = '<label' . ($id ? ' for="' . e($id) . '"' : '') . '>';
			if (!$isCheckboxRadio) {
				$groupParts[] = e($label);
				$groupParts[] = '</label>';
			}
		}
		$groupParts[] = call_user_func_array([$this, $type], $arguments);
		if ($hasLabel && $isCheckboxRadio) {
			$groupParts[] = e($label);
			$groupParts[] = '</label>';
			$groupParts[] = '</div>';
		}
		if (isset($extraOptions['help-block'])) {
			$groupParts[] = '<p class="help-block">' . e($extraOptions['help-block']) . '</p>';
		}
		if ($errors->has($errorIdx)) {
			if (empty($extraOptions['errors_all'])) {
				$groupParts[] = '<p class="help-block"><strong>' . e($errors->first($errorIdx)) . '</strong></p>';
			}
			else {
				$groupParts[] = '<p class="help-block"><strong>';
				foreach ($errors->getBag('default')->get($errorIdx) as $error) {
					$groupParts[] = $error . '<br>';
				}
				$groupParts[] = '</strong></p>';
			}
		}
		$groupParts[] = '</div>';

		return implode("\n", $groupParts);
	}

	public function __call($name, $arguments)
	{
		if (substr($name, 0, 5) === 'group') {
			$type = lcfirst(substr($name, 5));
			return $this->group($type, $arguments);
		}
		else {
			throw new Exception("Undefined method \"$name\".");
		}
	}
}
