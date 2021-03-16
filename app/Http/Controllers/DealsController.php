<?php

namespace App\Http\Controllers;

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
            // CURLOPT_URL => 'https://korobook.bitrix24.ru/rest/1/h12qo8y69ztxnzal/' . $action,
            CURLOPT_URL => 'https://korobook.bitrix24.ru/rest/1/re5kvosyn1spsrv8/' . $action,
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
        $arDeal = self::bitrixAPI(["ID" => $id], 'crm.deal.get');
        if (isset($arDeal->error)) {
            dd($arDeal);
            return false;
        }
        $dealTitle =  $arDeal->result->TITLE;
        $comment = $arDeal->result->COMMENTS;

        $dealArr = self::commentParser($comment);
        $dealArr['params']['deal'] = $dealTitle;
        $dealArr['params']['addinfo'] = unserialize($arDeal->result->ADDITIONAL_INFO);
        $dealArr['params']['dealid'] = $id;
        $dealArr['params']['managernote'] = $arDeal->result->UF_CRM_1476173890;
        $dealArr['params']['Срок готовности'] = explode('T', $arDeal->result->CLOSEDATE)[0];
        $manager = self::bitrixAPI(['ID' => $arDeal->result->ASSIGNED_BY_ID], 'user.get');

        if (!isset($manager->error))
            $dealArr['params']['manager'] = $manager->result[0]->NAME . ' ' . $manager->result[0]->LAST_NAME;
        else $dealArr['params']['manager'] = '';

        if ($arDeal->result->UF_CRM_1615928997) $dealArr['params']['Объединен'] = $arDeal->result->UF_CRM_1615928997;
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
