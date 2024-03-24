<?php namespace Wisemood\LaravelTcmbDoviz;
use Wisemood\LaravelTcmbDoviz\Kur;
class Doviz extends \Eloquent {

   protected $table = 'dolar_rates';
   public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        'BanknoteBuying',
        'BanknoteSelling',
        'date_', // tarih
    ];



   public static function enYakinKur($tarih = null,$currency = null)
   {
        if (empty($tarih)) {
            $tarih = date("Y-m-d");

        }
      $get = Kur::getKur($tarih,$currency);

      return $get;
   }


}
