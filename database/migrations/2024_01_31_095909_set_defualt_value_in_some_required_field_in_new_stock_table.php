<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('new_stock', function (Blueprint $table) {
            $table->string('locked_by')->default('')->change();
            $table->string('new_sku')->nullable()->change();
            $table->text('faults')->nullable()->change();
            $table->string('touch_id_working')->nullable()->change();
            $table->string('location')->nullable()->change();
            $table->decimal('part_cost',10,2)->default(0)->change();
            $table->decimal('unlock_cost',10,2)->default(0)->change();
            $table->string('purchase_country')->nullable()->change();
            $table->decimal('original_sale_price',10,2)->default(0)->change();
            $table->longText('notes')->nullable()->change();
            $table->string('product_version')->nullable()->change();
            $table->string('sold_by')->nullable()->change();
            $table->string('sold_in')->nullable()->change();
            $table->boolean('imei_problem_reported')->default(0)->change();
            $table->string('in_repair_previous_status')->nullable()->change();
            $table->longText('lost_reason')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->default(null)->change();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('new_stock', function (Blueprint $table) {
            //
        });
    }
};
