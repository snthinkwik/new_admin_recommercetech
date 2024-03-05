<?php namespace App\Validation;

use App\Models\User;
use Exception;
use Illuminate\Validation\Validator as BaseValidator;

use  Illuminate\Contracts\Translation\Translator;



class Validator extends BaseValidator {

	/**
	 * @var array
	 */
	protected $arrayData;

	/**
	 * @var array
	 */
	protected $arrayRules = [];

	public function __construct(Translator $translator,
                                array $data ,
                                array $rules = array() ,
                                array $messages = array() ,
                                array $customAttributes = array()
    )
	{

		if (!$rules && method_exists($this, 'rules')) {
			$rules = $this->rules();
		}
		if (!$messages && method_exists($this, 'customMessages')) {
			$messages = $this->customMessages();
		}
		parent::__construct($translator, $data, $rules, $messages, $customAttributes);
	}

	public function fails()
	{

		$fails = parent::fails();

		foreach ($this->arrayRules as $field => $ruleDefinition) {
			$rules = explode('|', $ruleDefinition);
			foreach ($rules as $rule) {
				$method = 'validateArray' . ucfirst($rule);
				$fails = !$this->$method($field) || $fails;
			}
		}


		return $fails;
	}

	protected function validateBelongsTo($attribute, $value, $parameters)
	{
		if (strpos($attribute, 'imeis') === 0) {
			return Models\User::ownsImei($parameters[0], $value);
		}
		throw new Exception("Unknown attribute \"$attribute\".");
	}

	protected function validateImei($attribute, $value, $parameters)
	{
		return ctype_digit($value) && strlen($value) === 15;
	}

	/**
	 * This method validates the uniqueness of input based on the array of other input supplied using `self::setArray()`.
	 * @param string $fieldName
	 * @param bool $allowEmpty
	 * @return bool
	 */
	protected function validateArrayUnique($fieldName, $allowEmpty = false)
	{
		$countByValue = [];

		foreach ($this->arrayData as $row) {
			if (!isset($countByValue[$row[$fieldName]])) $countByValue[$row[$fieldName]] = 0;
			$countByValue[$row[$fieldName]]++;
		}

		if (
			$countByValue[$this->data[$fieldName]] > 1 &&
			(!$allowEmpty || trim($this->data[$fieldName]))
		) {
			$fieldNameHumanFriendly = str_replace('_', ' ', $fieldName);
			$this->errors()->add($fieldName, "The value for $fieldNameHumanFriendly field is not unique.");
			return false;
		}

		return true;
	}

	/**
	 * This method validates the uniqueness of input based on the array of other input supplied using `self::setArray()`.
	 * @param string $fieldName
	 * @return bool
	 */
	protected function validateArrayUniqueOrEmpty($fieldName)
	{
		return $this->validateArrayUnique($fieldName, true);
	}

	/**
	 * Set array of input used for validation. Used for situations when you have to validate more than one thing and the
	 * validation has to check the items against one another. In Laravel you have for instance the 'unique' validation
	 * rule, but it checks again the database. Using setArray() you can validate against input that isn't saved in the
	 * database yet.
	 * @param array $data
	 * @param array $rules
	 */
	public function setArray(&$data, $rules)
	{
		$this->arrayData = $data;
		$this->arrayRules = $rules;
	}

}
