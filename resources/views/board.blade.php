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
    @if ($templates->count() > 0)
    @for ($line = 1; $line <= $lineCount; $line++) <div class="line-number">
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
@endphp
<div class="line" line={{ $line }}>

    @foreach ($templates->where('line', $line)->sortBy('position') as $template)

    @php
    $position = $template->position;
    $template=$templates->where('line',$line)->where('position', $position)->first();
    $style="box-shadow:inset 0px -61px 0px 0px hsl( ".(string)((int)$template->masters[0]*14).",
    50%,80%)";

    $emptyCount = $template->realPosition-$position-$allEmptyCounts;
    @endphp

    @for ($i = 0; $i < $emptyCount; $i++) <div class="template">
</div>
@php
$allEmptyCounts ++;
@endphp
@endfor

<div class="template" style="{{ $style ?? '' }}">

    {{-- <p>Реальная позиция {{ $template->realPosition }}</p> --}}
    <p class="title">{{ $template->taskname }}</p>
    <hr>
    <p class="description">Мастера:
        @foreach ($template->masters as $item)

        {{ $Users->find($item)->name }},
        @endforeach
    </p>
    <p class="description">Мини-параметры:
        <span>{{ $template->miniparams? implode(', ', $template->miniparams):'' }}</span>
    </p>
    <br>
    <p class="description">Базовое / расчетное время: <span>{{ $template->producttime }} /
            {{ $template->paramtime }} мин.</span>
    </p>
    <p class="description">ID предыдущей задачи: <span>{{ $template->taskidbefore }}</span></p>
    <p class="description">Буфер: <span>{{ $template->buffer }} мин.</span></p>
    <br>
    <p class="description">Запретный период:
        @foreach ($template->periods ?? [] as $item)
        <span>{{ $item }}</span>,
        @endforeach
    </p>
    <p class="description">Условия выполнения:
        @foreach ($template->conditions ?? [] as $item)
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
    <form class="plus" action="/newtemplate" method="get">
        <input type="hidden" name="time" value="{{ time() }}">
        <input type="hidden" name="line" value="{{ $line }}">
        <input type="hidden" name="position" value="{{ $position+1 }}">
        <input type="hidden" name="productid" value="{{ $productId }}">
        <input type="submit" value="+">
    </form>
</div>
</div>
@endfor

@endif
</div>

<button class="newLine" onclick="newLine()">Добавить линию</button>
<script>
    // рендерит новую линию элементов с плюсом
    function newLine() {
        let productId = document.getElementById('product-id').value
        let lines = document.querySelectorAll('.line')
        let lastLine
        let templateCount
        if (lines.length > 0) {
            lastLine =  Number(lines[lines.length - 1].getAttribute('line'))
            templateCount = lines[0].querySelectorAll('.template').length //кол-во пустых блоков в строке
        } else {
            lastLine =  0
            templateCount = 10
        }
        
        let plusElem = document.createElement('form')
        plusElem.className = 'plus'
        plusElem.action = '/newtemplate'
        plusElem.method = 'get'
        let html = ''
        // html += '<input type="hidden" name="_token" value="' + document.querySelector('input[name="_token"]').value +
        //     '">'
        html += '<input type="hidden" name="line" value="' + Number(lastLine + 1) + '">'
        html += '<input type="hidden" name="position" value="1">'
        html += '<input type="hidden" name="productid" value="' + productId + '">'
        html += '<input type="submit" value="+">'
        plusElem.innerHTML = html

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

            if (index == 1) {
                newTemplate.appendChild(plusElem)
            }
        }
    }

</script>