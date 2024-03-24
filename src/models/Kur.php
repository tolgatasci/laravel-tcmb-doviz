<?php namespace Wisemood\LaravelTcmbDoviz;

class Kur{

    // tcmb git ve tarihe göre veri çek

    static function getKur($tarih = null,$currency = null){
        // https://www.tcmb.gov.tr/kurlar/202403/20032024.xml
        // https://www.tcmb.gov.tr/kurlar/Ym/dmY.xml

        $content = @file_get_contents("http://www.tcmb.gov.tr/kurlar/".date("Ym", strtotime($tarih))."/".date("dmY", strtotime($tarih)).".xml");

        while(!$content){
                $tarih = date("Y-m-d", strtotime("-1 day", strtotime($tarih)));
                $content = file_get_contents("http://www.tcmb.gov.tr/kurlar/".date("Ym", strtotime($tarih))."/".date("dmY", strtotime($tarih)).".xml");
        }


        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        $parse =  Kur::parse($array);



        $parse['request_date'] = date("Y-m-d", strtotime($tarih));

        $x = Kur::insertKur($parse,$currency);


        return $x;

    }
    public static function insertKur($data,$currency = null){
        $tarih = $data["request_date"];
        unset($data["request_date"]);
        try{
        $kularim = [];
        foreach($data as $kex => $val){
            $doviz = Doviz::where('date_', $tarih)->where('code',$val["CurrencyCode"])->first();

            if(!empty($doviz)){

                $kularim[$kex] = $doviz->toArray();

                continue;
            }

            $kur = new Doviz();
            $kur->name = $val["CurrencyName"];
            $kur->code = $val["CurrencyCode"];
            $kur->BanknoteBuying = $val["BanknoteBuying"];
            $kur->BanknoteSelling = $val["BanknoteSelling"];
            $kur->date_ = $tarih;
            $kur->save();
            $kularim[$kex] = $kur;
        }

        }catch(\Exception $e){

            return $e->getMessage();
        }

        return $kularim[$currency];
    }
    static function parse($array){
        $data = [];
        // CurrencyName, CurrencyCode, Tarih, BanknoteBuying, BanknoteSelling
        foreach($array["Currency"] as $val){
            // only $val["@attributes"]["CurrencyCode"] == "USD" or "EUR"
            if($val["@attributes"]["CurrencyCode"] != "USD" && $val["@attributes"]["CurrencyCode"] != "EUR") continue;
            $val = (array) $val;
            $data[$val["@attributes"]["CurrencyCode"]] = [
                "CurrencyName" => $val["CurrencyName"],
                "CurrencyCode" => $val["@attributes"]["CurrencyCode"],
                "Tarih" => $array["@attributes"]["Tarih"],
                "BanknoteBuying" => $val["BanknoteBuying"],
                "BanknoteSelling" => $val["BanknoteSelling"]
            ];

        }
        return $data;
    }
}
