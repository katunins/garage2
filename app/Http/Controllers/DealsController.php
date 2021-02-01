<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DealsController extends Controller
{

    // bitrix24Api
    static function bitrixAPI($arData, $action)
    {
        //TITLE
        $queryData = http_build_query($arData);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://korobook.bitrix24.ru/rest/1/h12qo8y69ztxnzal/' . $action,
            CURLOPT_POSTFIELDS => $queryData,
        ));
        //расшифровка полученных данных

        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result);
    }

    //Парсер комментария в сделке
    static function commentParser($comment)
    {
        $comment = trim(strip_tags($comment, '<br>'));

        $arr = explode('<br>', $comment);
        $prod = 0; //кол-во номер продукта в сделке
        foreach ($arr as $row) {
            if ($row != "") {
                if (strpos($row, ':')) {
                    $data = explode(':', $row);
                    $param = trim($data[0]);
                    if (strpos($param, 'Количество') === false && strpos($param, 'Рабочих дней') === false) $value = trim($data[1]);
                    else $value = (int) trim($data[1]);
                    if ($param == 'Комментарий' || $prod === false) {
                        $prod = false;
                        $deal['params'][$param] = $value;
                    } else {
                        $deal['products'][$prod][$param] = $value;
                    }
                } elseif ($prod !== false) {
                    $prod++;
                    $deal['products'][$prod]["productname"] = trim($row);
                } else $deal['params'][trim($row)] = true;
            }
        }
        return $deal;
    }

    static function getDeal($id)
    {
        // +"COMMENTS": "<br><b>Фотокниги</b><br><br><b>Формат </b>:  20х20 см  <br><b>Материал обложки </b>:  Toronto Toronto Белый  <br><b>Персонализация </b>:  Без персонализации  <b ▶"
        // +"ADDITIONAL_INFO": "a:12:{s:15:"DELIVERYSERVICE";s:8:"СДЭК";s:32:"МЕТОД ДОСТАВКИ ИД";s:1:"4";s:13:"PAYMENTSYSTEM";s:25:"Оплата картой";s:28:"МЕТОД ОПЛАТЫ ИД";s:1:"6";s:9:"ORDERPAID ▶"
        $arDeal = self::bitrixAPI(["ID" => $id], 'crm.deal.get');
        if (isset($arDeal->error)) return false;
        $addInfo = unserialize($arDeal->result->ADDITIONAL_INFO);
        $dealTitle =  $arDeal->result->TITLE;
        $comment = $arDeal->result->COMMENTS;
        $dealArr = self::commentParser($comment);
        $dealArr ['params']['deal'] = $dealTitle;
        return $dealArr;
    }

    // POST в коробук
    static function rest($requestData)
    {

        $ch = curl_init('https://korobook.ru/garage2/rest.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);

        // Или предать массив строкой: 
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
    }
}
