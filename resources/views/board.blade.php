{{-- $templates --}}
{{-- $lineCount --}}
{{-- $productId --}}
{{-- $positionCount --}}

<link rel="stylesheet" href="/css/board.css">
<link rel="stylesheet" href="/css/general.css">

<h1>
    <a class="to-main-page" href="/"></a>
    {{ $productTitle }}
</h1>
<input id="product-id" type="hidden" value="{{ $productId }}">
<div class="lineBlock">
    {{-- @if ($templates->count() > 0) --}}
    <blade
        for|%20(%24line%20%3D%201%3B%20%24line%20%3C%3D%20%24lineCount%3B%20%24line%2B%2B)%20%3Cdiv%20class%3D%26%2334%3Bline-number%26%2334%3B%3E>

        {{-- @if ($templates->where('line', $line)->count()>0) --}}
        <p>
            Линия {{ $line }}
        </p>
        <div>
            <form action="/moveline" method="get">
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="time" value="{{ time() }}">
                <input type="hidden" name="lineshift" value="-1">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input class="mini-arrows @if ($line==1) hide @endif" type="submit" value="↑">
            </form>
            <form action="/moveline" method="get">
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="time" value="{{ time() }}">
                <input type="hidden" name="lineshift" value="1">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input class="mini-arrows @if ($line==$lineCount) hide @endif" type="submit" value="↓">
            </form>
        </div>
</div>

@php
    $allEmptyCounts = 0; //кол-во пустых ячеек в строке
    $lastLineTemplates = 0; //кол-во шаблонов в полседний линии
@endphp
<div class="line" line={{ $line }}>
    <blade
        foreach|%20(%24templates-%3Ewhere(%26%2339%3Bline%26%2339%3B%2C%20%24line)-%3EsortBy(%26%2339%3Bposition%26%2339%3B)%20as%20%24template)>

        @php
            $lastLineTemplates ++;
            $position = $template->position;
            $template=$templates->where('line',$line)->where('position', $position)->first();
            $style="box-shadow:inset 0px -61px 0px 0px hsl( ".(string)((int)$template->masters[0]*14).",
            50%,80%)";
            $emptyCount = $template->realPosition-$position-$allEmptyCounts;
        @endphp

        <blade
            for|%20(%24i%20%3D%200%3B%20%24i%20%3C%20%24emptyCount%3B%20%24i%2B%2B)%20%3Cdiv%20class%3D%26%2334%3Btemplate%26%2334%3B%3E>
</div>
@php
    $allEmptyCounts ++;
@endphp
@endfor

<div class="template" style="{{ $style ?? '' }}">

    {{-- <p>Реальная позиция {{ $template->realPosition }}
    </p> --}}
    <p class="title">{{ $template->taskname }}</p>
    <hr>
    <p class="description">Мастера:
        @foreach($template->masters as $item)

            {{ $Users->find($item)->name }},
        @endforeach
    </p>
    <p class="description">Мини-параметры:
        <span>{{ $template->miniparams? implode(', ', $template->miniparams):'' }}</span>
    </p>
    <br>
    <p class="description">Базовое / расчетное время: <span>{{ $template->producttime }} мин /
            {{ $template->paramtime ?? 0 }} сек.</span>
    </p>
    <p class="description">ID предыдущей задачи: <span>{{ $template->taskidbefore }}</span></p>
    <p class="description">Буфер: <span>{{ $template->buffer }} мин.</span></p>
    <br>
    <p class="description">Запретный период:
        @foreach($template->periods ?? [] as $item)
            <span>{{ $item }}</span>,
        @endforeach
    </p>
    <p class="description">Условия выполнения:
        @foreach($template->conditions ?? [] as $item)
            <span>{{ $item['condition'].' '.$item['equal'].' '.$item['value'] }}</span>
            <br>
        @endforeach
    </p>
    <br>
    <div class="buttons arrows-buttons">
        {{-- ←, →, ↑, ↓ --}}

        <form action="/movetemplate" method="get">
            <input type="hidden" name="time" value="{{ time() }}">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="position" value="{{ $position }}">
            <input type="hidden" name="lineshift" value="0">
            <input type="hidden" name="positionshift" value="-1">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <input type="hidden" name="templateid" value="{{ $template->id }}">
            <input @if ($position==1) class="inactive" @endif type="submit" value="←">
        </form>
        <form action="/movetemplate" method="get">
            <input type="hidden" name="time" value="{{ time() }}">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="position" value="{{ $position }}">
            <input type="hidden" name="lineshift" value="0">
            <input type="hidden" name="positionshift" value="1">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <input type="hidden" name="templateid" value="{{ $template->id }}">
            <input @if ($position==$templates->where('line', $line)->max('position'))
            class="inactive"@endif
            type="submit" value="→">
        </form>
        <form action="/movetemplate" method="get">
            <input type="hidden" name="time" value="{{ time() }}">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="position" value="{{ $position }}">
            <input type="hidden" name="lineshift" value="-1">
            <input type="hidden" name="positionshift" value="0">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <input type="hidden" name="templateid" value="{{ $template->id }}">
            <input @if ($line==1) class="inactive" @endif type="submit" value="↑">
        </form>
        <form action="/movetemplate" method="get">
            <input type="hidden" name="time" value="{{ time() }}">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="position" value="{{ $position }}">
            <input type="hidden" name="lineshift" value="1">
            <input type="hidden" name="positionshift" value="0">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <input type="hidden" name="templateid" value="{{ $template->id }}">
            <input @if ($templates->where('line', $line)->max('position') == 1 &&
            $templates->where('line',
            $line+1)->count() == 0) class="inactive" @endif type="submit" value="↓">
        </form>
    </div>
    <div class="buttons edit-buttons">
        <p>id: {{ $template->id }}</p>
        <form class="remove" action="/deletetemplate" method="get">
            <input type="hidden" name="time" value="{{ time() }}">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="position" value="{{ $position }}">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <input type="hidden" name="templateid" value="{{ $template->id }}">
            <input type="submit" value="x">
        </form>
        <form class="edit" action="/edittemplate" method="get">
            <input type="hidden" name="time" value="{{ time() }}">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="position" value="{{ $position }}">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <input type="hidden" name="templateid" value="{{ $template->id }}">
            <input type="submit" value="✎">
        </form>
        <form class="edit" action="/copytemplate" method="get">
            <input type="hidden" name="time" value="{{ time() }}">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="position" value="{{ $position }}">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <input type="hidden" name="templateid" value="{{ $template->id }}">
            <input type="submit" value="+">
        </form>
    </div>
</div>
@endforeach
<div class="template">
    <div class="plus">
        <form action="/newtemplate" method="get">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="time" value="{{ time() }}">
            <input type="hidden" name="position" value="{{ ($position ?? 0 )+1 }}">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <input class="plus-big-button" type="submit" value="+">
            <div style="height: 20px"></div>
        </form>

        <form class="clone" action="/clonetemplate" method="get">
            <input type="hidden" name="line" value="{{ $line }}">
            <input type="hidden" name="position" value="{{ ($position ?? 1 ) }}">
            <input type="hidden" name="productid" value="{{ $productId }}">
            <label for="copy">Скопировать шаблон</label>

            <br>
            <input type="text" name="cloneid" size="5" value="" placeholder="id: ">
            <input type="submit" value="Скопировать">

            <div style="margin-top: 5px">
                <select style="width: 145px" onchange="selectTemplateToClone ()">
                    <option disabled selected>Выберите шаблон</option>
                    @foreach($standartTemplates as $item)
                        <option value={{ $item->id }}>{{ $item->taskname }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

</div>
</div>

{{-- @endif --}}
@endfor

{{-- @endif --}}
</div>
@if($lastLineTemplates>0)
    <button class="newLine" onclick="newLine()">Добавить линию</button>
@endif
<script>
    // подставляет выбранный шаблон для клонирования в поле ID
    function selectTemplateToClone() {
        let selectElem = event.target
        let id = selectElem.querySelectorAll('option')[selectElem.selectedIndex].value

        selectElem.parentNode.parentNode.querySelector('input[name="cloneid"]').value = id
    }

    // рендерит новую линию элементов с плюсом
    function newLine() {
        let productId = document.getElementById('product-id').value
        let lines = document.querySelectorAll('.line')
        let lastLine
        let templateCount
        if (lines.length > 0) {
            lastLine = Number(lines[lines.length - 1].getAttribute('line'))
            templateCount = lines[0].querySelectorAll('.template').length //кол-во пустых блоков в строке
        } else {
            lastLine = 0
            templateCount = 10
        }

        let plusElem = document.querySelector('.plus').cloneNode(true);
        let arrParam = [{
            param: 'line',
            newValue: Number(lastLine + 1)
        }, {
            param: 'position',
            newValue: 1
        }]
        arrParam.forEach(item => {
            plusElem.querySelectorAll(`input[name="${item.param}"]`).forEach(elem => elem.value = item.newValue)
        });

        console.log()


        let newLine = document.createElement('div')

        newLine.className = 'line'
        newLine.setAttribute('line', lastLine + 1)

        document.querySelector('.lineBlock').appendChild(newLine)
        for (let index = 1; index < templateCount + 1; index++) {
            let newTemplate = document.createElement('div')
            newTemplate.className = 'template'
            newTemplate.setAttribute('position', index)
            newTemplate.setAttribute('line', lastLine + 1)
            newLine.appendChild(newTemplate)

            if (index == 1) newTemplate.appendChild(plusElem);
        }

        let button = document.querySelector('button.newLine')
        button.parentNode.removeChild(button)
    }

</script>
