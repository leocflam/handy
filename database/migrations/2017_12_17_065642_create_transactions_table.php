<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('amount', 18, 2);
            // assume it can transact to other banks while other banks id might not use pure number
            $table->string('from_account_id')->nullable()->default(null);
            $table->string('from_bank_name')->default('Handy Test Bank Ltd.');
            $table->string('to_account_id')->nullable()->default(null);
            $table->string('to_bank_name')->default('Handy Test Bank Ltd.');
            $table->bigInteger('bank_account_id')->unsigned();
            $table->decimal('service_fee', 18, 2);
            $table->string('description')->default('Example Description');
            $table->string('type')->default('deposit');
            $table->string('flag')->default('debit');
            $table->timestamps();

            $table->foreign('bank_account_id')
                    ->references('id')
                    ->on('bank_accounts')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
