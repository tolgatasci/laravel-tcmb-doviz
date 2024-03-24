<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDoviz extends Migration {

   const TABLE = 'dolar_rates';

   /**
    * Run the migrations.
    *
    * @return void
    */
   public function up()
   {
      Schema::create('dolar_rates', function (Blueprint $table) {
         $table->increments('id');
	 $table->string('name');
	 $table->string('code');
         $table->decimal('BanknoteBuying', 10, 6);
         $table->decimal('BanknoteSelling', 10, 6);
	 $table->date('date_')->index();
         $table->timestamps();
      });
   }

   /**
    * Reverse the migrations.
    *
    * @return void
    */
   public function down()
   {
      Schema::drop("dolar_rates");
   }

}
