<?php namespace App\Csv;


use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\NoopWordInflector;
use Exception;

class Parser
{
	protected $filePath;
	protected $fileResource;
	protected $options;
	protected $header;
	protected $rowIdx = 1;
	protected $allowedFields = [];

	/**
	 * @param string $filePath
	 * @param array $options bool ['header'] Indicates whether the file has a header with column names or not.
	 * @param array $options callable ['headerFilter'] Optional method that will process the header so that you can
	 *                                                 normalise column names.
	 * @param array $options array ['valueRules'] Rules according to which the column should be processed. They look
	 *                                            like this: ['Column' => 'rule', 'Column 2' => function($value){}].
	 *                                            As you can see they can be strings or callables. If it's a string
	 *                                            then they refer to methods in this class, for instance
	 *                                            'device-capacity' for the ruleDeviceCapacity() method.
	 *                                            If they're callables then they will receive the value to process as
	 *                                            the only argument.
	 * @param array $options array ['headerMapping'] Optional mapping of header columns, for instance ['abc' => 'def']
	 *                                               will cause the 'abc' header to be rewritten to 'def' in the results.
	 *                                               Please note that this mapping is used after using the optional
	 *                                               header filter function (see 'headerFilter' option) so if your header
	 *                                               is "Some column" and your filter rewrites it to 'some_column' then
	 *                                               you'll have to use ['some_column' => 'another_column] if you want
	 *                                               to rename it.
	 */
	public function __construct($filePath, $options = [])
	{
		// Fix Windows (old Mac?) line breaks.
		exec("perl -i -pe 's/\r(?!\n)/\r\n/g' " . $filePath);

		$this->filePath = $filePath;
		$this->fileResource = fopen($this->filePath, 'r');
		$this->options = $options + $this->getDefaults();
		if ($this->options['header']) {
			$this->header = $this->parseHeader();
		}
	}

	public function getAllRows()
	{
		$rows = [];

		$row = $this->getRow();
		while ($row) {
			$rows[] = $row;
			$row = $this->getRow();
		}

		return $rows;
	}

	public function getRow()
	{
		if (!$this->fileResource) {
			throw new Exception("File resource empty");
		}

		// Retrieving the row and checking if the file has ended.
		$row = fgetcsv($this->fileResource);
		if (
			// Empty row after the last row of data. Expected.
			!$row ||
			// This one's a little more unusual. I've seen rows with the same number of columns as in the actual data
			// rows, but each field was empty. Let's check for this and treat it as end of file too.
			!trim(implode('', $row))
		) {
			$this->closeFile();
			return null;
		}

		// Assigning values to keys if header present.
		if ($this->header) {
			$lengthDiff = count($row) - count($this->header);
			if ($lengthDiff > 0) {
				$row = array_slice($row, 0, count($this->header));
			}
			if ($lengthDiff < 0) {
				for ($i = 0; $i < $lengthDiff; $i++) $row[] = '';
			}

			$data = array_combine($this->header, $row);

			if ($this->allowedFields) {
				foreach ($data as $k => $v) {
					if (!in_array($k, $this->allowedFields)) {
						unset($data[$k]);
					}
				}
			}
		}
		// Otherwise simple assignment.
		else {
			$data = $row;
		}

		$this->rowIdx++;

		$data = array_map('trim', $data);

        $inflector=new Inflector(new NoopWordInflector(), new NoopWordInflector());
		// Extra processing of values.
		if ($this->options['valueRules']) {
			foreach ($this->options['valueRules'] as $column => $rule) {
				if (isset($data[$column])) {
					$data[$column] = is_string($rule)
						? $this->{'rule' . $inflector->classify($rule)}($data[$column])
						: $rule($data['column']);
				}
			}
		}

		return $data;
	}

	protected function ruleAmount($value)
	{
		return preg_replace('/[^\d.]/', '', $value);
	}

	protected function ruleDeviceCapacity($value)
	{
		return preg_replace('/\D/', '', $value);
	}

	protected function parseHeader()
	{
		$row = fgetcsv($this->fileResource);
		if (!$row || !isset($row[0])) {
			throw new Exception("No header in the file");
		}
		$row = array_map('trim', $row);
		if (isset($this->options['headerFilter'])) {
			$row = array_map($this->options['headerFilter'], $row);
		}

		if (isset($this->options['headerMapping'])) {
			foreach ($row as &$value) {
				if (isset($this->options['headerMapping'][$value])) {
					$value = $this->options['headerMapping'][$value];
				}
			}
			unset($value);
		}
		return $row;
	}

	public function getRowIdx()
	{
		return $this->rowIdx;
	}

	protected function getDefaults()
	{
		return [
			'header' => true,
			'valueRules' => [],
		];
	}

	protected function closeFile()
	{
		if ($this->fileResource) {
			fclose($this->fileResource);
		}
		$this->fileResource = null;
	}

	public function __destruct()
	{
		$this->closeFile();
	}
}
