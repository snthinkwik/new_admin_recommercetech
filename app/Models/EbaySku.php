<?php

namespace App\Models;

use App\Csv\Parser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\File;

class EbaySku extends Model
{
    use HasFactory;
    protected $table = 'new_ebay_skus';

    public static function parseValidateCsv(File $csv, $salesPriceRequired = false) {

        $csvParser = new Parser($csv->getRealPath(), [
            'headerFilter' => function($columnName) {
                $columnName = strtolower($columnName);

                $columnName = preg_replace('/\W+/', '_', $columnName);
                return $columnName;
            },
        ]);

        $rows = $csvParser->getAllRows();
        $errors = [];
        foreach ($rows as $i => $row) {

            $rules = [
                'sku' => 'required',
                'owner' => 'required'
            ];

            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                $errors[] = ['rowIdx' => $i, 'errors' => $validator->errors()];
            }
        }

        return [$rows, $errors];
    }
}
