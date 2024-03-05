<?php namespace App\Csv;

use Doctrine\Common\Inflector\Inflector;

class array_parser
{
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
        try{
            $this->fileResource = fopen($filePath, 'r');
        }catch(Exception $e){
            return;
        }
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
		if ($this->header && isset($this->options['headerFilter'])) {
            $data = array_map($this->options['headerFilter'], $row);
            $data = array_combine($this->header[0], $data[0]);
		}
		// Otherwise simple assignment.
		else {
			$data = $row;
        }

		$this->rowIdx++;

		$data = array_map('trim', $data);

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
