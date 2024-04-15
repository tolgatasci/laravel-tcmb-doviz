<?php namespace Wisemood\LaravelTcmbDoviz;

class Kur{

    // tcmb git ve tarihe göre veri çek

    static function getKur($tarih = null,$currency = null){
        // https://www.tcmb.gov.tr/kurlar/202403/20032024.xml
        // https://www.tcmb.gov.tr/kurlar/Ym/dmY.xml
        $istenilen_tarih = $tarih;

        $ch = curl_init();
        if(date("Y-m-d", strtotime($tarih)) == date("Y-m-d")){
            $x_url = "https://www.tcmb.gov.tr/kurlar/today.xml";
        }else{
            $x_url ="https://www.tcmb.gov.tr/kurlar/".date("Ym", strtotime($tarih))."/".date("dmY", strtotime($tarih)).".xml";
        }

        curl_setopt($ch, CURLOPT_URL, $x_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Host: www.tcmb.gov.tr',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0',
            'Upgrade-Insecure-Requests: 1',
        ]);

        $content = curl_exec($ch);
        curl_close($ch);



        $infos = curl_getinfo($ch);
        $info = $infos["http_code"];

        $i=0;
        while(true){
            if(preg_match('/BanknoteSelling/i', $content)){
                break;
            }
            $tarih = date("Y-m-d", strtotime("-1 day", strtotime($tarih)));

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://www.tcmb.gov.tr/kurlar/".date("Ym", strtotime($tarih))."/".date("dmY", strtotime($tarih)).".xml");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            //max execute time
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Host: www.tcmb.gov.tr',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0',
                'Upgrade-Insecure-Requests: 1',
            ]);

            $infos = curl_getinfo($ch);
            $info = $infos["http_code"];

            $content = curl_exec($ch);
            if(preg_match('/BanknoteSelling/i', $content)){
                break;
            }
            curl_close($ch);
            if($i==15){
                break;
            }
            $i++;
        }

        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        $parse =  Kur::parse($array);



        $parse['request_date'] = date("Y-m-d", strtotime($istenilen_tarih));

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
                $kur->ForexBuying = $val["ForexBuying"];
                $kur->ForexSelling = $val["ForexSelling"];
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
                "ForexBuying" => $val["ForexBuying"],
                "ForexSelling" => $val["ForexSelling"]
            ];

        }
        return $data;
    }
}
