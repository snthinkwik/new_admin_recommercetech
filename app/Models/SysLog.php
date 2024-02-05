<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysLog extends Model
{
    use HasFactory;

    protected $table = 'log';

    protected $fillable = ['content', 'user_id', 'stock_id', 'sale_id'];

    public static function log($content, $userId = null, $stockId = null, $saleId = null)
    {
        if (is_array($stockId)) {
            foreach ($stockId as $id) {
                self::create([
                    'content' => $content,
                    'user_id' => $userId,
                    'stock_id' => $id,
                    'sale_id' => $saleId,
                ]);
            }
        }
        else {
            self::create([
                'content' => $content,
                'user_id' => $userId,
                'stock_id' => $stockId,
                'sale_id' => $saleId,
            ]);
        }
    }

}
