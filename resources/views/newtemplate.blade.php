<link rel="stylesheet" href="css/newtemplate.css">
<link rel="stylesheet" href="css/general.css">

{{-- 
    $line
    $position
    $productId
    $template - в случае редактирования
--}}

{{-- {{ dd($template->first()) }} --}}
<h1>
    <a class="to-main-page" href="/">←</a>
    Новый шаблон</h1>
<p>
    Линия {{ $line }}, Позиция {{ $position }}, Продукт {{ $productId }}
</p>

<form action="/savetemplate" method="POST">
    @csrf
    <input type="hidden" name="productid" value="{{ $productId }}">
    <input type="hidden" name="line" value="{{ $line }}">
    <input type="hidden" name="position" value="{{ $position }}">
    @isset($template)
    <input type="hidden" name="templateid" value="{{ $template->id }}">
    @endisset

    <div class="input-blocks">
        <div class="input-group">
            @error('taskname')
            <p class="alert">{{ $message }}</p>
            @enderror
            <label for="taskname">Название задачи</label>
            <input class="max-size" type="text" id="taskname" name="taskname"
                value="{{ old('taskname', $template->taskname ?? '') }}" placeholder="Подготовка картона">
            @error('params')
            <p class="alert">{{ $message }}</p>
            @enderror
            <label for="params">Мини-параметры</label>
            <input class="max-size" type="text" id="params" name="params"
                value="{{ old('params', $template->params ?? '') }}" placeholder="Тип печати/Форзац">
            <p class="help">Параметры, которые будет видеть мастер в задаче, не открывая ее. (указываются через слеш,
                без пробелов). Основные параметры (номер
                заказа и название продукта указывать не нужно, так как они присутсвуют в задаче по умолчанию)</p>
        </div>

        <div class="input-group">
            @error('masters')
            <p class="alert">{{ $message }}</p>
            @enderror
            <label for="masters">ID мастеров</label>
            <input class="mini-size" type="text" id="masters" name="masters"
                value="{{ old('masters', $template->masters ?? '') }}" placeholder="1/2">
            <p class="help">Список мастеров (указываются через слеш, без пробелов). Если первому мастеру нет возможности
                поставить задачу, то робот ищет свободное время у
                следующего</p>

            <label for="taskidbefore">ID предыдущей задачи</label>
            <input class="mini-size" type="text" id="taskidbefore" name="taskidbefore"
                value="{{ old('taskidbefore', $template->taskidbefore ?? '') }}" placeholder="123">
            <p class="help">Если задача должна быть поставлена после времени окончания задачи из другой линии, то
                укажите ID задачи из другой линии
        </div>

        <div class="json-input-group">
            <label>Условия создания задачи</label>
            <input class="middle-size" type="text" name="condition1"
                value="{{ old('condition1', $template->condition1 ?? '') }}" placeholder="Формат=20х20/25х25/30х30">
            <input class="middle-size" type="text" name="condition2"
                value="{{ old('condition2', $template->condition2 ?? '') }}" placeholder="Тип печати!=шелк">
            <input class="middle-size" type="text" name="condition3"
                value="{{ old('condition3', $template->condition3 ?? '') }}" placeholder="Доставка">

            <p class="help">Задача будет поставлена, если выполнится одно из условий.

                Параметр и значения нужно вводить без пробелов. Лучше делать copy-paste из параметров заказа, так как к
                примеру литера Х в 20х20 может быть и русской и латинецей. Выражения: "=" - значение в параметре
                содержит параметр условия, "!="
                - тоже самое, но не содержит. "==" - точное совпадение, "!==" - точное не совпадене. Также можно
                указывать несколько значений для условия, разделив их через слеш без проеблов</p>
        </div>



        <div class="input-group">
            @error('producttime')
            <p class="alert">{{ $message }}</p>
            @enderror
            <label for="time">Базовое время задачи, мин.</label>
            <input class="mini-size" type="text" id="producttime" name="producttime"
                value="{{ old('producttime', $template->producttime ?? '') }}" placeholder="10">
            <p class="help">Время в минутах для выполнения задачи одного продукта не зависимо от формата и разворотов
            </p>
            {{-- @error('paramtime')
            <p class="alert">{{ $message }}</p>
            @enderror --}}
            <label for="time">Расчетное время задачи, мин.</label>
            <input class="mini-size" type="text" id="paramtime" name="paramtime"
                value="{{ old('paramtime', $template->paramtime ?? '') }}" placeholder="0.5">
            <p class="help">Время в минутах за квадратный дециметр площади. Если необходимо установить время меньше
                минуты, то можно использовать десятичные числа с точнкой: 0.5 = 30 секунд, 0.25 =
                15 сек.</p>
        </div>

        <div class="input-group">
            @error('buffer')
            <p class="alert">{{ $message }}</p>
            @enderror
            <label for="buffer">Буферное время, мин.</label>
            <input class="mini-size" type="text" id="buffer" name="buffer"
                value="{{ old('buffer', $template->buffer ?? '') }}" placeholder="3600">
            <p class="help">Время, спустя которое будет поставлена следующая задача в конвеере. В каждую задачу также
                закладывается стандартный запасный период</p>
        </div>
        <div class="json-input-group">
            <label>Запретные периоды времени</label>
            <input class="mini-size" type="text" name="period1" value="{{ old('period1', $template->period1 ?? '') }}"
                placeholder="9:00-11:00">
            <input class="mini-size" type="text" name="period2" value="{{ old('period2', $template->period2 ?? '') }}"
                placeholder="16:00-18:00">
            <p class="help">Промежутки времени в которые нелься планировать задачу. Перерыв на обед указывать не нужно,
                как он учитывается по умолчанию
            </p>
        </div>


    </div>

    <div class="buttons">
        <a href="{{ url()->previous() }}">Назад</a>
        {{-- @if ($template)
        <a class="remove" href="{{  }}">Удалить</a>
        @endif --}}
        <input type="submit" value="Сохранить">

    </div>

</form>