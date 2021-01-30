<link rel="stylesheet" href="css/newtemplate.css">

{{-- 
    $line
    $position
    $productId
    $template - в случае редактирования
--}}

{{-- {{ dd($template->first()) }} --}}
<h1>Новый шаблон</h1>
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
            <p class="help">Параметры, которые необходимо видеть мастеру в кратком описании в дополнение к основным</p>
        </div>

        <div class="input-group">
            @error('masters')
            <p class="alert">{{ $message }}</p>
            @enderror
            <label for="masters">ID мастеров</label>
            <input class="mini-size" type="text" id="masters" name="masters"
                value="{{ old('masters', $template->masters ?? '') }}" placeholder="1/2">
            <p class="help">Если первому мастеру нет возможности поставить задачу, то робот ищет возможность у
                следующего</p>
            
            <label for="taskidbefore">ID предыдущей задачи</label>
            <input class="mini-size" type="text" id="taskidbefore" name="taskidbefore"
                value="{{ old('taskidbefore', $template->taskidbefore ?? '') }}" placeholder="123">
            <p class="help">Новая задача будет поставлена после предварительной</p>
        </div>

        <div class="json-input-group">
            <label>Условия создания задачи</label>
            <input class="middle-size" type="text" name="condition1"
                value="{{ old('condition1', $template->condition1 ?? '') }}" placeholder="Формат=20х20/25х25/30х30">
            <input class="middle-size" type="text" name="condition2"
                value="{{ old('condition2', $template->condition2 ?? '') }}" placeholder="Тип печати!=шелк">
            <input class="middle-size" type="text" name="condition3"
                value="{{ old('condition3', $template->condition3 ?? '') }}" placeholder="Доставка">

            <p class="help">Параметр и значения нужно вводить также, как и в параметрах заказа (20x20 - может быть
                русское "Х" или наоборот английский "X-Икс". Поэтому лучше copy-paste). <br>Выражения "=" - равно, "!="
                - не равно. Возможые значения можно разделять слешем. Он означет, что уловие выполниться при
                соответствии одному или другому значению</p>
        </div>



        <div class="input-group">
            @error('producttime')
            <p class="alert">{{ $message }}</p>
            @enderror
            <label for="time">Базовое время задачи, мин.</label>
            <input class="mini-size" type="text" id="producttime" name="producttime"
                value="{{ old('producttime', $template->producttime ?? '') }}" placeholder="10">
            <p class="help">Время в минутах задачи одного продукта не зависимо от параметров</p>
            {{-- @error('paramtime')
            <p class="alert">{{ $message }}</p>
            @enderror --}}
            <label for="time">Расчетное время задачи, мин.</label>
            <input class="mini-size" type="text" id="paramtime" name="paramtime"
                value="{{ old('paramtime', $template->paramtime ?? '') }}" placeholder="0.5">
            <p class="help">Время в минтах за расчтеный параметр. К примеру в фотокниге расчетный параметр - 1кв см
                площади. Если необходимо установить время меньше минуты, то можно использовать 0.5 = 30 секунд, 0.25 =
                15 сек.</p>
        </div>

        <div class="input-group">
            @error('buffer')
            <p class="alert">{{ $message }}</p>
            @enderror
            <label for="buffer">Буферное время, мин.</label>
            <input class="mini-size" type="text" id="buffer" name="buffer"
                value="{{ old('buffer', $template->buffer ?? '') }}" placeholder="3600">
            <p class="help">Буфреное время - период в минутах на сушку клея или на возможное ожидание из-за брака).
                Следующая задача в линии будет поставлена спустя этот период. Также есть стандартное запасное время
                между задачами</p>
        </div>
        <div class="json-input-group">
            <label>Допустимые периоды времени</label>
            <input class="mini-size" type="text" name="period1" value="{{ old('period1', $template->period1 ?? '') }}"
                placeholder="9:00-11:00">
            <input class="mini-size" type="text" name="period2" value="{{ old('period2', $template->period2 ?? '') }}"
                placeholder="16:00-18:00">
            <p class="help">Допустимый промежуток времени, в который можно ставить данную задачу. Вводить без пробелов
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