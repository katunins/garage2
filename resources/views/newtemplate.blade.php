<link rel="stylesheet" href="css/newtemplate.css">
<link rel="stylesheet" href="css/general.css">
{{-- 
    $line
    $position
    $productId
    $template - в случае редактирования
    $allParams;
--}}

{{-- {{ dd($allParams) }} --}}
{{-- сделаем скрытые параметры массива значений --}}
@foreach ($allParams as $key=>$item)
    @foreach ($item as $i)
        <input class="all-params" type="hidden" name="{{ $key }}" value="{{ $i }}">
    @endforeach
@endforeach

<h1>
    <a class="to-main-page" href="/"></a>
    Новый шаблон</h1>
<p>
    Линия {{ $line }}, Позиция {{ $position }}, Продукт {{ $productId }}
</p>

<form action="/savetemplate" method="POST">
    @csrf
    <input type="hidden" name="productid" value="{{ $productId }}">
    <input type="hidden" name="line" value="{{ $line }}">
    <input type="hidden" name="position" value="{{ $position }}">
    @isset ($template)
        <input type="hidden" name="templateid" value="{{ $template->id }}">
    @endisset

    <div class="input-blocks">
        <div class="input-group">
            @error ('taskname')
                <p class="alert">{{ $message }}</p>
            @enderror
            <label for="taskname">Название задачи</label>
            <input class="max-size" type="text" id="taskname" name="taskname"
                value="{{ old('taskname', $template->taskname ?? '') }}"
                placeholder="Подготовка картона">

            <br><br>
            <label> Мини-параметры</label>


            <div class="param-input-help">
                @for ($i = 0; $i < 3; $i++)
                    <div>
                        <input name="miniparams[{{ $i }}]" list="conditions" placeholder="Выберете параметр"
                            value="{{ old('miniparams')[$i] ?? $template->miniparams[$i] ?? '' }}">
                        <button type="button" onclick="this.parentNode.querySelector('input').value=''">×</button>
                    </div>
                @endfor
            </div>

            <p class="help">Параметры, которые будет видеть мастер в задаче, не открывая ее. Основные параметры (номер
                заказа и название продукта указывать не нужно, так как они присутсвуют в задаче по умолчанию)</p>
        </div>

        <div class="input-group">
            <label for="masters">ID мастеров</label>
            @error ('masters.*')
                <p class="alert">{{ $message }}</p>
            @enderror
            <div class="param-input-help">
                @for ($i = 0; $i < 3; $i++)
                    <div>
                        <input name="masters[{{ $i }}]" list="masters" placeholder="Выберете мастера"
                            value="{{ old('masters')[$i] ?? $template->masters[$i] ?? '' }}">
                        <button type="button" onclick="this.parentNode.querySelector('input').value=''">×</button>
                    </div>
                @endfor
                <datalist id="masters">
                    @foreach ($Users as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </datalist>

                {{-- @php
                    $mastersArr = explode('/', $template->masters ?? '');
@endphp
@foreach ($Users as $item)
                    <button type="button"
                        class="@if (array_search($item->id, $mastersArr) !==false) active-help-button @endif"
                        id="{{ $item->id }}" onclick="clickMasterButtons(this)">
                + {{ $item->name }}
                </button>
                @endforeach
                <button type="button" onclick="document.getElementById('masters').value=''"
                    style="background-color: rgb(201, 201, 201)">Очистить все</button> --}}
            </div>

            <label for="taskidbefore">ID предыдущей задачи</label>
            <input class="mini-size" type="text" id="taskidbefore" name="taskidbefore"
                value="{{ old('taskidbefore', $template->taskidbefore ?? '') }}"
                placeholder="123">
            <p class="help">Если задача должна быть поставлена после времени окончания задачи из другой линии, то
                укажите ID задачи из другой линии
        </div>

        <div class="json-input-group">
            <label>Условия создания задачи</label>
            @for ($i = 0; $i < 3; $i++) <div>
                <div>
                    <input class="middle-size condition" name="conditions[{{ $i }}][condition]" list="conditions"
                        placeholder="Выберете параметр" onchange="setHelpValues('help-value-{{ $i }}')"
                        value="{{ old('conditions')[$i]['condition'] ?? $template->conditions[$i]['condition'] ?? '' }}">



                    <input name="conditions[{{ $i }}][equal]" list="equals" placeholder="?" size="30"
                        value="{{ old('conditions')[$i]['equal'] ?? $template->conditions[$i]['equal'] ?? '' }}">
                    <button type="button"
                        onclick="this.parentNode.querySelectorAll('input').forEach(el=>el.value=''); setHelpValues('{{ $i }}');">×</button>
                    <input class="max-size param-input" name="conditions[{{ $i }}][value]"
                        value="{{ old('conditions')[$i]['value'] ?? $template->conditions[$i]['value'] ?? '' }}"
                        onclick="setHelpValues('help-value-{{ $i }}')">

                </div>
                <p class="help" id="help-value-{{ $i }}"></p>
        </div>
        @endfor
        <datalist id="conditions">
            @foreach ($allParams as $param=>$values)
                <option value="{{ $param }}">
            @endforeach
        </datalist>
        <datalist id="equals">
            @foreach ([
                'содержит'=>'=',
                'не содержит'=>'!=',
                'равно'=>'==',
                'не равно'=>'!=='
                ] as $key=>$value)
                <option value="{{ $value }}">{{ $key }}</option>
            @endforeach
        </datalist>
    </div>

    <div class="input-group">
        @error ('producttime')
            <p class="alert">{{ $message }}</p>
        @enderror
        <label for="time">Базовое время задачи, мин.</label>
        <input class="mini-size" type="text" id="producttime" name="producttime"
            value="{{ old('producttime', $template->producttime ?? '') }}"
            placeholder="10">
        <p class="help">Время в минутах для выполнения задачи одного продукта не зависимо от формата и разворотов
        </p>
        {{-- @error('paramtime')
            <p class="alert">{{ $message }}</p>
        @enderror --}}
        <label for="time">Расчетное время задачи, мин.</label>
        <input class="mini-size" type="text" id="paramtime" name="paramtime"
            value="{{ old('paramtime', $template->paramtime ?? '') }}"
            placeholder="0.5">
        <p class="help">Время в минутах за квадратный дециметр площади. Если необходимо установить время меньше
            минуты, то можно использовать десятичные числа с точнкой: 0.5 = 30 секунд, 0.25 =
            15 сек.</p>
    </div>

    <div class="input-group">
        @error ('buffer')
            <p class="alert">{{ $message }}</p>
        @enderror
        <label for="buffer">Буферное время, мин.</label>
        <input class="mini-size" type="text" id="buffer" name="buffer"
            value="{{ old('buffer', $template->buffer ?? '') }}"
            placeholder="3600">
        <p class="help">Время, спустя которое будет поставлена следующая задача в конвеере. В каждую задачу также
            закладывается стандартный запасный период</p>
    </div>
    <div class="json-input-group">
        <label>Запретные периоды времени</label>

        @for ($i = 0; $i < 2; $i++)
            <input class="mini-size" name="periods[{{ $i }}]" placeholder="Укажите период"
                value="{{ old('periods')[$i] ?? $template->periods[$i] ?? '' }}">
        @endfor

        {{-- <div class="param-input-help">
@for ($i = 0; $i < 3; $i++)
                    <div>
                        <input name="masters[{{ $i }}]" list="masters" placeholder="Выберете мастера"
        value="{{ old('masters')[$i] ?? $template->masters[$i] ?? '' }}">
        <button type="button" onclick="this.parentNode.querySelector('input').value=''">×</button>
    </div> --}}

    {{-- <input class="mini-size" type="text" name="period1"
            value="{{ old('period1', $template->period1 ?? '') }}"
    placeholder="9:00-11:00">
    <input class="mini-size" type="text" name="period2"
        value="{{ old('period2', $template->period2 ?? '') }}"
        placeholder="16:00-18:00"> --}}
    <p class="help">Промежутки времени в которые нельзя планировать задачу. Перерыв на обед указывать не нужно,
        как он учитывается по умолчанию. Пример 14:00-15:00 без пробелов!
    </p>
    </div>


    </div>

    <div class="buttons">
        <a href="{{ url()->previous() }}">Назад</a>
        {{-- @if ($template)
        <a class="remove" href="{{}}">Удалить</a>
        @endif --}}
        <input type="submit" value="Сохранить">

    </div>

</form>
<script>
    function setHelpValues(elemId) {
        let valuesArr = []

        document.querySelectorAll('input[name="' + event.target.parentNode.querySelector('.condition').value + '"]')
            .forEach(el => valuesArr.push(el.value))
        html = ''
        valuesArr.forEach(el => {
            html += '<button '
            console.log ()
            if (event.target.parentNode.querySelector('.param-input').value.indexOf(el)!=-1) html += 'class="active-help-button" ';
            html += 'type="button" onclick="clickHelpButtons()">' + el + '</button>'
        })

        // let paraminput = event.target.parentNode.querySelector('.param-input')
        // paraminput.value = ''
        document.getElementById(elemId).innerHTML = html
    }

    function clickHelpButtons() {
        let paramInput = event.target.parentNode.parentNode.querySelector('.param-input')

        if (event.target.classList.contains('active-help-button')) {
            // удаляем 
            event.target.classList.remove('active-help-button')
            if (paramInput.value.indexOf('/') < 0) paramInput.value = '';
            paramInput.value = paramInput.value.replace(event.target.innerHTML + '/', '')
            paramInput.value = paramInput.value.replace('/' + event.target.innerHTML, '')

        } else {
            // добавляем
            event.target.classList.add('active-help-button')
            if (paramInput.value == '') paramInput.value += event.target.innerHTML;
            else paramInput.value += '/' + event.target.innerHTML;
        }


    }

    function clickMasterButtons(elem) {

        mastersIDinput = document.getElementById('masters')
        mastersIDstring = mastersIDinput.value

        if (elem.classList.contains('active-help-button')) {
            // удаляем  мастера
            elem.classList.remove('active-help-button')
            // console.log (mastersIDstring)
            // mastersIDinput.value = mastersIDstring.value.replace('/'+elem.id. '')
            mastersIDinput.value = mastersIDstring.replace(elem.id + '/', '')
            mastersIDinput.value = mastersIDstring.replace('/' + elem.id, '')
            if (mastersIDstring.indexOf('/') < 0) mastersIDinput.value = '';
        } else {
            // добавляем мастера
            elem.classList.add('active-help-button')
            if (mastersIDstring == '') mastersIDinput.value += elem.id;
            else mastersIDinput.value += '/' + elem.id;
        }
    }

</script>
