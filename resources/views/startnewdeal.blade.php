{{-- @newDeals --}}
{{--     +"ID": "31199"
    +"TITLE": "20 #8581"
    +"TYPE_ID": "SALE"
    +"STAGE_ID": "3"
    +"PROBABILITY": "20"
    +"CURRENCY_ID": "RUB"
    +"OPPORTUNITY": "10682.70"
    +"IS_MANUAL_OPPORTUNITY": "N"
    +"TAX_VALUE": "0.00"
    +"LEAD_ID": null
    +"COMPANY_ID": "0"
    +"CONTACT_ID": "5717"
    +"QUOTE_ID": null
    +"BEGINDATE": "2021-03-17T03:00:00+03:00"
    +"CLOSEDATE": "2021-03-29T03:00:00+03:00"
    +"ASSIGNED_BY_ID": "8"
    +"CREATED_BY_ID": "1"
    +"MODIFY_BY_ID": "1"
    +"DATE_CREATE": "2021-03-17T09:21:09+03:00"
    +"DATE_MODIFY": "2021-03-18T13:12:50+03:00"
    +"OPENED": "Y"
    +"CLOSED": "N"
    +"COMMENTS": "<br><b>Фотокниги</b><br><br><b>Формат </b>:  20х20 см  <br><b>Материал обложки </b>:  Флок Флок Светло-бирюзовый  <br><b>Персонализация </b>:  Окно с фотографие ▶"
    +"ADDITIONAL_INFO": "a:12:{s:15:"DELIVERYSERVICE";s:8:"СДЭК";s:32:"МЕТОД ДОСТАВКИ ИД";s:1:"4";s:13:"PAYMENTSYSTEM";s:25:"Оплата картой";s:28:"МЕТОД ОПЛАТЫ ИД";s:1:"6";s:9:"ORDERPAID ▶"
    +"LOCATION_ID": null
    +"CATEGORY_ID": "0"
    +"STAGE_SEMANTIC_ID": "P"
    +"IS_NEW": "N"
    +"IS_RECURRING": "N"
    +"IS_RETURN_CUSTOMER": "Y"
    +"IS_REPEATED_APPROACH": "N"
    +"SOURCE_ID": null
    +"SOURCE_DESCRIPTION": null
    +"ORIGINATOR_ID": "9"
    +"ORIGIN_ID": "8581"
    +"UTM_SOURCE": null
    +"UTM_MEDIUM": null
    +"UTM_CAMPAIGN": null
    +"UTM_CONTENT": null
    +"UTM_TERM": null --}}

<link rel="stylesheet" href="/css/startnewdeal.css">
<link rel="stylesheet" href="/css/general.css">

<h1>
    <a class="to-main-page" href="/"></a>
    Новые оплаченные сделки
</h1>

<div class="container">
    <div class="new-deals-block">
        @foreach ($newDeals as $item)
            <form class="new-deal-item" action="deal2tasks" method="POST">

                @csrf
                <input type="hidden" name="dealid" value="{{ $item->ID }}">
                <div>
                    <span class="title">{{ $item->TITLE }}</span>
                    @if ($item->dublicateCount > 0)
                        <div class="dublicate-alert">К этой сделке уже созданы задачи: {{ $item->dublicateCount }}
                            шт.</div>
                    @endif

                    <span>Оплачена
                        {{ explode('T', $item->UF_CRM_1538403264)[0] }}</span>
                </div>
                <div class="data-block">
                    <div class="comments-block cloud-style">
                        <div class="colud-style__title">
                            Комментарий
                        </div>
                        @php
                            echo $item->COMMENTS;
                        @endphp
                    </div>

                    <div class="right-data-block">
                        <div class="params-block cloud-style">
                            <div class="colud-style__title">
                                Срок изготовления
                            </div>
                            <div>
                                <span
                                    class="value">{{ explode('T', $item->CLOSEDATE)[0] }}
                            </div>
                            <div>
                                <span class="value">
                                    {{ $item->UF_CRM_1551355423 }} рабочих дней</span>
                            </div>
                        </div>

                        @if ($item->UF_CRM_1615928997)

                            <div class="params-block cloud-style">
                                <div class="colud-style__title">
                                    Сделка объединена
                                </div>
                                <div>
                                    <span class="value">{{ $item->UF_CRM_1615928997 }}
                                </div>
                            </div>
                        @endif

                        <div class="params-block cloud-style">
                            <div>
                                <div class="colud-style__title">
                                    Заметка к сделке
                                </div>
                                <input type="hidden" name="old_manager_note" value="{{ $item->UF_CRM_1476173890 }}">
                                <textarea name="manager_note" id="manager_note" rows="4">

@php echo $item->UF_CRM_1476173890; @endphp

                                </textarea>
                            </div>
                        </div>

                        <div class="submit-button-block">

                            <div>
                                <input class="button" type="submit" value="Запустить в работу">
                            </div>
                            <div>
                                <input type="checkbox" name="custom_deal">
                                <label for="custom_deal">Не стандартная сделка</label>
                            </div>
                        </div>

                    </div>
                </div>

            </form>
        @endforeach
    </div>
</div>
