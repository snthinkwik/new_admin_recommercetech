<?php namespace App\Support;

use App\Country;

class ReportParser
{

	public static function getSerialNumber($report)
	{
		$serial = "";
		// SN line is required to find colour
		if(preg_match('/SN\s*:\s+(?<serialNumber>.*?)(\.|\s*<br \/>)/', $report, $serialNumber) && isset($serialNumber['serialNumber'])) {
			$serial = $serialNumber['serialNumber'];
		} elseif(preg_match('/SN\s*:\s+(?<serialNumber>.*?)(\.|\s*<br>)/', $report, $serialNumber) && isset($serialNumber['serialNumber'])) {
			$serial = $serialNumber['serialNumber'];
		} elseif(preg_match('/Serial Number\s*:\s+(?<serialNumber>.*?)(\.|\s*<br)/', $report, $serialNumber) && isset($serialNumber['serialNumber'])) {
			$serial = $serialNumber['serialNumber'];
		} elseif(preg_match('/Serial Number\s*:\s+(?<serialNumber>.*?)(\.|\s*<br\/>)/', $report, $serialNumber) && isset($serialNumber['serialNumber'])) {
			$serial = $serialNumber['serialNumber'];
		} elseif(preg_match('/Serial Number\s*:\s+(?<serialNumber>.*?)(\.|\s*<br\\/>)/', $report, $serialNumber) && isset($serialNumber['serialNumber'])) {
			$serial = $serialNumber['serialNumber'];
		}

		return $serial;
	}

	public static function getModel($report) {
		$model = "";

		if(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br \/>)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br>)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br\/>)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br\\/>)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		}

		if(isset($modelLine) && is_string($modelLine)) {
			$words = explode(" ", $modelLine);
			if(count($words) >= 2 && strtolower($words[0]) == 'iphone') {
				if(isset($words[2]) && strtolower($words[2]) == 'plus') {
					$modelName = $words[0]." ".$words[1]." ".$words[2]; // for example iPhone 6s Plus
				} else {
					$modelName = $words[0]." ".$words[1]; // for example iPhone 6s (and third word = silver)
				}
				$model = $modelName;
			} elseif(count($words) >= 3 && strtolower($words[0]) == 'apple' && strtolower($words[1]) == 'iphone') {
				if(strtolower($words[3]) == 'plus') {
					$modelName = $words[1]." ".$words[2]." ".$words[3]; // for example Apple iPhone 6s Plus
				} else {
					$modelName = $words[1]." ".$words[2]; // for example Apple iPhone 6s
				}
				$model = $modelName;
			}
		}

		return $model;
	}

	public static function getColour($report)
	{
		$colour = "";
		// model line is required to find colour
		if(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br \/>)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br>)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br\/>)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br\\/>)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Model\s*:\s+(?<modelLine>.*?)(\.|\s*<br)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		} elseif(preg_match('/Device\s*:\s+(?<modelLine>.*?)(\.|\s*<br)/', $report, $modelLine) && isset($modelLine['modelLine'])) {
			$modelLine = $modelLine['modelLine'];
		}

		if(isset($modelLine) && is_string($modelLine)) {
			$modelLine = strtolower($modelLine);
			$modelLine = str_replace(",", " ", $modelLine);
			$modelLine = ucwords($modelLine);
			$colours = self::getColourMapping();
			foreach($colours as $term => $c) {
				if(strpos($modelLine, $term) !== false) {
					return $c;
				}
			}
		}

		return $colour;

	}

	public static function getColourMapping()
	{
		return [
			'Red' => 'Red',
			'Black' => 'Black',
			'White' => 'White',
			'Aqua' => 'Aqua',
			'Blue' => 'Blue',
			'Brown' => 'Brown',
			'Green' => 'Green',
			'Lime' => 'Lime',
			'Maroon' => 'Maroon',
			'Orange' => 'Orange',
			'Pink' => 'Pink',
			'Purple' => 'Purple',
			'Silver' => 'Silver',
			'Violet' => 'Violet',
			'Yellow' => 'Yellow',
			'Mixed' => 'Mixed',
			'Space Grey' => 'Space Grey',
			'Space grey' => 'Space Grey',
			'Space gray' => 'Space Grey',
			'Space Gray' => 'Space Grey',
			'Rose Gold' => 'Rose Gold',
			'Rose gold' => 'Rose Gold',
			'RGLD' => 'Rose Gold',
			'PNK' => 'Pink',
			'SLVR' => 'Silver',
			'Grey' => 'Grey',
			'Gray' => 'Gray',
			'Gold' => 'Gold'
		];
	}

	public static function getNetwork($report, $samsung = false)
	{
		if(preg_match('/Original Carrier\s*:\s+(?<network>.*?)(\.|\s*<br>)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Network\s*:\s+(?<network>.*?)(\.|\s*<br>)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*<br>)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\<br>)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\<br\/>)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\<br\\/>)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\<br)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\< br)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\&lt br)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\Country:)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\")/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Carrier\s*:\s+(?<network>.*?)(\.|\s*\<)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		} elseif(preg_match('/Sim-lock\s*:\s+(?<network>.*?)(\.|\s*\<)/', $report, $network) && isset($network['network'])) {
			$network = $network['network'];
		}

		if(isset($network) && !is_array($network)) {
			$networkMappings = self::getNetworkMapping();
			if(isset($networkMappings[$network])) {
				$networkMatched = $networkMappings[$network];
				return $networkMatched;
			} elseif(strpos(strtolower($network), 'open') !== false) {
				$networkMatched = 'Unlocked';
				return $networkMatched;
			} elseif(strpos(strtolower($network), 'unlocked') !== false) {
				$networkMatched = 'Unlocked';
				return $networkMatched;
			} elseif($samsung == true) {
				// check if there's country in found network
				$countries = array_map('strtolower', Country::orderBy('name')->lists('name'));
				if(strpos_array(strtolower($network), $countries)) {
					$networkMatched = "Foreign Network";
					return $networkMatched;
				}
			}
		}

		return false;
	}

	protected static function getNetworkMapping()
	{
		return [
			'Unlock' => 'Unlocked',
			'Unlocked' => 'Unlocked',
			'Unlock Service' => 'Unlocked',
			'Retail Unlock' => 'Unlocked',
			'UK Vodafone' => 'Vodafone',
			'UK VODAFONE' => 'Vodafone',
			'Orange' => 'EE',
			'T-MOBILE' => 'EE',
			'T-Mobile' => 'EE',
			'Hutchinson' => 'Three',
			'O2' => 'O2',
			'UK O2 Tesco' => 'O2',
			'UK O2 TESCO' => 'O2',
			'EMEA' => 'EMEA',
			'EMEA Service' => 'EMEA',
			'EMEA SERVICE' => 'EMEA',
			'UK Hutchison' => 'Three',
			'UK TMobile Orange' => 'EE',
			'France SFR' => 'Foreign Network',
			'UK Virgin Mobile' => 'EE',
			'UK T-Mobile Orange' => 'EE',
			'UK T-MOBILE ORANGE POLICY' => 'EE',
			'UK ORANGE' => 'EE',
			'UK HUTCHISON LOCKED POLICY' => 'Three',
			'UK Hutchison Unlocked' => 'Unlocked',
			'Hong Kong Smartone Unlocked' => 'Unlocked',
			'Ireland Hutchison/O2 Locked' => 'Foreign Network',
			'Norway Telenor Unlocked' => 'Unlocked',
			'Singapore Reseller Unlocked' => 'Unlocked',
			'US Verizon LTE Unlocked' => 'Unlocked',
			'Global BrightStar Unlock Activation' => 'Unlocked',
			'Multi-Mode Unlock' => 'Unlocked',
			'UK Virgin Mobile Unlocked' => 'Unlocked',
			'Netherlands Vodafone' => 'Foreign Network',
			'US AT&T Reseller' => 'AT&T',
			'UK Carphone Flex activation policy' => 'Locks to First Sim',
			'UK Reseller Flex Policy' => 'Locks to First Sim',
			'Denmark Hutchison' => 'Foreign Network',
			'United Kingdom Open' => 'Unlocked',
			'United Kingdom H3g Hutchison' => 'Three',
			'United Kingdom Everything Everywhere' => 'EE',
			'United Kingdom Vodafone' => 'Vodafone',
			'Poland Heyah' => 'Foreign Network',
			'United Kingdom O2' => 'O2',
			'O2 Tesco Activation Policy' => 'O2',
			'O2 Tesco UK Activation Policy' => 'O2',
			'Vodafone Activation Policy' => 'Vodafone',
			'270 - UK Vodafone Activation Policy' => 'Vodafone',
			'269 - UK O2 Tesco Activation Policy' => 'O2',
			'UK O2 Tesco Activation Policy' => 'O2',
			'302 - UK T-Mobile Orange Activation Policy' => 'EE',
			'55 - EMEA Service'  => 'EMEA',
			'2252 - UK Hutchison Locked Policy' => 'Three',
			'UK Hutchison Locked Policy' => 'Three',
			'22 - JP Softbank' => 'Foreign Network',
			'23 - US AT&T Activation Policy' => 'AT&T',
			'US AT&T Activation Policy'=> 'AT&T',
			'52 - US GSM Service Policy' => 'US GSM',
			'2136 - US T-Mobile Locked Activation Policy' => 'T-Mobile USA',
			'US T-Mobile Locked Activation Policy' => 'T-Mobile USA',
			'2018 - US Sprint iPhone 4S Policy' => 'Sprint USA',
			' US Sprint iPhone 4S Policy' => 'Sprint USA',
			'2279 - US AT&T/Cricket Locked Policy' => 'AT&T',
			'US AT&T/Cricket Locked Policy' => 'AT&T',
			'2360 - US TracFone / StraightTalk Locked Policy' => 'Foreign Network',
			'2360 - US TracFone/StraightTalk Locked Policy' => 'Foreign Network',
			'2174 - US Sprint/MVNO Locked Policy' => 'Sprint USA',
			'US Sprint/MVNO Locked Policy' => 'Sprint USA',
			'UK Vodafone Activation Policy' => 'Vodafone',
			'UK T-Mobile Orange Activation Policy' => 'EE',
			'US Verizon Locked Policy' => 'Verizon',
			'Japan SoftBank' => 'Other',
			'US TracFone/StraightTalk Locked Policy' => 'Other',
			'IE Vodafone' => 'Other',
			'Japan NTT DOCOMO Activation Policy' => 'Other',
			'Japan Service Policy' => 'Other',
			'PT Optimus (Orange)' => 'Other',
			'United States Country Default Flex Policy' => 'Other',
			'IE Hutchison/O2 Locked Policy' => 'Other',
			'Mexico Nextel Locked Policy' => 'Other'
		];
	}

	public static function getLocked($report)
	{
		$lockedRes = "";

		if(preg_match('/Sim-Lock: <font color="008000">(?<locked>.*?)(\.|\s*<\/font>)/', $report, $locked) && isset($locked['locked'])) {
			$locked = $locked['locked'];
		} elseif(preg_match('/Sim-Lock: <font color="green">(?<locked>.*?)(\.|\s*<\/font>)/', $report, $locked) && isset($locked['locked'])) {
			$locked = $locked['locked'];
		} elseif(preg_match('/Sim-Lock: <font color=\"green\">(?<locked>.*?)(\.|\s*<\/font>)/', $report, $locked) && isset($locked['locked'])) {
			$locked = $locked['locked'];
		} elseif(preg_match('/Sim-Lock\s*:\s+(?<locked>.*?)(\.|\s*<br)/', $report, $locked) && isset($locked['locked'])) {
			$locked = $locked['locked'];
		} elseif(preg_match('/Is Network Locked\s*:\s+(?<locked>.*?)(\.|\s*<br)/', $report, $locked) && isset($locked['locked'])) {
			$locked = $locked['locked'];
		} elseif(preg_match('/Sim-lock\s*:\s+(?<locked>.*?)(\.|\s*\<)/', $report, $locked) && isset($locked['locked'])) {
			$locked = $locked['locked'];
		}

		if($locked == "UNLOCKED" || $locked == "FALSE" || $locked == "Unlocked" || $locked == "Unlocked") {
			$lockedRes = "Unlocked";
		}
		elseif(strpos($report, 'Sim-Lock: <font color=\"green\">Unlocked<\/font>') !== false) {
			$lockedRes = "Unlocked";
		}

		return $lockedRes;
	}

	public static function getIcloudLock($report)
	{
		$icloudLockRes = "";

		if(preg_match('/iCloud Lock\s*:\s+(?<locked>.*?)(\.|\s*<br)/', $report, $locked) && isset($locked['locked'])) {
			$icloudLockRes = $locked['locked'];
		}

		return $icloudLockRes;
	}
}