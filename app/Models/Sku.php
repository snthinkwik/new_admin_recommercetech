<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
    use HasFactory;

    protected $table = 'sku';

    protected static $definitions = null;

    protected $fillable = ['type', 'short', 'long'];

    /**
     * Each type is defined with the following properties:
     * - length: how many characters it takes
     * - type:
     *   - default: simple single word
     *   - separate_words: should be treated as separate words
     *   - numeric: should be treated as a number
     */
    protected static $typeDefinitions = [
        'name' => [
            'length' => 7,
            'type' => 'separate_words',
        ],
        'colour' => [
            'length' => 2,
            'type' => 'default',
        ],
        'capacity' => [
            'length' => 3,
            'type' => 'numeric',
        ],
        'network' => [
            'length' => 2,
            'type' => 'default',
        ],
        'grade' => [
            'length' => 1,
            'type' => 'default',
        ],
    ];

    public static function parse($sku, $parts = null)
    {
        $sku = trim($sku);
        if (!$parts) {
            $parts = ['name', 'colour', 'capacity', 'network', 'grade'];
        }

        $defs = self::getDefinitions();

        $parsed = [];

        $totalLength = 0;
        foreach ($parts as $part) {
            $length = self::$typeDefinitions[$part]['length'];
            $skuSlice = substr($sku, $totalLength, $length);
            $totalLength += $length;
            $parsed[$part] = [
                'short' => $skuSlice,
                'long' => null
            ];
            if (isset($defs[$part][$skuSlice])) {
                $parsed[$part]['long'] = $defs[$part][$skuSlice];
            }
        }

        return $parsed;
    }

    /**
     * Creates a unique short code of the specified type from the given value.
     */
    public static function getShort($type, $long)
    {
        if (!trim($long)) {
            $long = 'Unknown';
        }

        $length = self::$typeDefinitions[$type]['length'];
        list($word, $remainder) = self::getWord($long, $length, self::$typeDefinitions[$type]['type']);
        // If the remainder is empty or insufficient to create a short code, we create a range of integers that we can
        // use. For instance if the $long is "green" and $length is 5 (full length of the word, which results in no
        // remainder) then we'll create a range from 1 to 9999, meaning the short code can be for instance GREE1 or
        // GRE75 or G9999.
        $numericFiller = ['min' => 1, 'max' => pow(10, $length -1) - 1];
        $defs = self::getDefinitions();
        $short = $word;

        if (!isset($defs[$type])) {
            $defs[$type] = [];
        }

        $i = $numericFiller['min'];
        do {
            // Short code not used yet. Create new database row and return the code.
            if (!isset($defs[$type][$short])) {
                $defs[$type][$short] = $long;
                self::$definitions = $defs;
                self::create(['type' => $type, 'short' => $short, 'long' => $long]);
                return $short;
            }
            // Short code already used and matches the long word. Just return the code.
            elseif ('' . $defs[$type][$short] === "$long") {
                return $short;
            }
            // Find alternative
            else {
                if ($remainder) {
                    $short = substr($short, 0, -1) . array_shift($remainder);
                }
                elseif ($i <= $numericFiller['max']) {
                    $short = substr($short, 0, -strlen($i)) . $i;
                    $i++;
                }
                else {

                    return "Couldn't create a short code.";

                    //throw new Exception("Couldn't create a short code.");
                }
            }
        }
        while (true);
    }

    /**
     * Returns a non-unique short word for the given value and a list (possibly empty) of the remaining characters in
     * the word that can help in creating the final unique short word.
     *
     * @param string $value Value to create a short word for.
     * @param int $length Desired length of the short word.
     * @param string $type Type of the $value. See self::$typeDefinitions
     *
     * @return array On the first index you'll get the short word. On the second index you'll get an array with the
     *               remainder of the word. Remainder of the word is an array of characters that can be help in creating
     *               the final unique short word. For instance if $value is "green" and length is 2 then on the first
     *               index you'll get "GR" and in the remainder you'll get ["e", "n"].
     */
    protected static function getWord($value, $length, $type)
    {
        if ($type === 'separate_words') {
            $words = preg_split('/ +/', $value);
            $word = '';
            foreach ($words as $part) {
                preg_match('/^\w\d*/', $part, $match);
                if ($match) $word .= $match[0];
            }
        }
        else {
            $word = preg_replace('/[^a-z0-9]/i', '', $value);
        }

        $remainder = array_unique(array_filter(str_split(strtoupper(substr($word, $length)))));
        $word = substr($word, 0, $length);
        $word = str_pad($word, $length, $type === 'numeric' ? 'X' : '0');
        return [
            strtoupper($word),
            $remainder,
        ];
    }

    public static function getDefinitions()
    {
        if (!self::$definitions) {
            $definitions = self::select('type', 'short', 'long')->get();

            foreach ($definitions as $def) {
                if (!isset(self::$definitions[$def['type']])) {
                    self::$definitions[$def['type']] = [];
                }

                self::$definitions[$def['type']][$def['short']] = $def['long'];
            }
        }

        return self::$definitions;
    }
}
