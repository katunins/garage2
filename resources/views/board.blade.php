{{-- $templates --}}
{{-- $lineCount --}}
{{-- $productId --}}
{{-- $positionCount --}}

<link rel="stylesheet" href="/css/board.css">
<h1><a class="to-main-page" href="/">←</a>
    {{ $productTitle }}</h1>
<div class="lineBlock">

    @for ($line = 1; $line <= $lineCount; $line++) <div class="line" line={{ $line }}>
        <div class="line-number">Линия {{ $line }}</div>
        @php
            //$lastIsEmpty = true; //предыдущий Template пустой, в нем стоит плюс
            // определим позицию элемента Plus
            $lastElemInLine = $templates->where('line', $line)->max('position');
            $plusPosition = $lastElemInLine? $lastElemInLine+1:1;
        @endphp

        @for ($position=1; $position <=$positionCount+1; $position++) @php $template=$templates->where('line',
            $line)->where('position', $position)->first();
        @endphp

        @if ($template && $template->taskidbefore)
            @php
                $prevTemplate = $templates->where('id', $template->taskidbefore)->first();
                $emptyCount = $prevTemplate?$prevTemplate->position+1-$position:0;
            @endphp
            @if ($emptyCount >0)
                @for ($x = 0; $x < $emptyCount; $x++) <div class="template">
</div>
@endfor
@endif
@endif
@php

    if ($template) $colorMaster = (int)explode('/', $template->masters)[0]*14;

@endphp
{{-- <div class="template" style="background-color: #{{ $colorMaster }};
"> --}}
{{-- <div class="template" @if($template) style="background-color: hsl({{ $colorMaster }},
50%, 85%);"@endif> --}}
<div class="template" @if($template) style="box-shadow:inset 0px -61px 0px 0px hsl({{ $colorMaster }}, 50%, 80%);" @endif>



    @if ($template)

        <p class="title">{{ $template->taskname }}</p>
        <hr>
        <p class="description">Мастер: <span>{{ $template->masters }}</span></p>
        <p class="description">Информация: <span>{{ $template->params }}</span></p>
        <br>
        <p class="description">Базовое / расчетное время: <span>{{ $template->producttime }} /
                {{ $template->paramtime }} мин.</span>
        </p>
        {{-- <p class="description">Расчетное время: <span>{{ $template->paramtime }}
        мин.</span></p> --}}
        <p class="description">ID предыдущей задачи: <span>{{ $template->taskidbefore }}</span></p>
        <p class="description">Буфер: <span>{{ $template->buffer }} мин.</span></p>
        <br>
        <p class="description">Доступный период:
            <span>{{ $template->period1 }}, {{ $template->period2 }}.</span>
        </p>
        <p class="description">Условия выполнения:
            <span> {{ $template->condition1 }} <br> {{ $template->condition2 }};
                {{ $template->condition3 }}</span>
        </p>
        <br>
        <div class="buttons arrows-buttons">
            {{-- ←, →, ↑, ↓ --}}

            <form action="/movetemplate" method="get">
                @csrf
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="position" value="{{ $position }}">
                <input type="hidden" name="lineshift" value="0">
                <input type="hidden" name="positionshift" value="-1">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input type="hidden" name="templateid" value="{{ $template->id }}">
                <input @if ($position==1) class="inactive" @endif type="submit" value="←">
            </form>
            <form action="/movetemplate" method="get">
                @csrf
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="position" value="{{ $position }}">
                <input type="hidden" name="lineshift" value="0">
                <input type="hidden" name="positionshift" value="1">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input type="hidden" name="templateid" value="{{ $template->id }}">
                <input @if ($position==$templates->where('line', $line)->max('position')) class="inactive" @endif
                type="submit" value="→">
            </form>
            <form action="/movetemplate" method="get">
                @csrf
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="position" value="{{ $position }}">
                <input type="hidden" name="lineshift" value="-1">
                <input type="hidden" name="positionshift" value="0">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input type="hidden" name="templateid" value="{{ $template->id }}">
                <input @if ($line==1) class="inactive" @endif type="submit" value="↑">
            </form>
            <form action="/movetemplate" method="get">
                @csrf
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="position" value="{{ $position }}">
                <input type="hidden" name="lineshift" value="1">
                <input type="hidden" name="positionshift" value="0">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input type="hidden" name="templateid" value="{{ $template->id }}">
                {{-- <input @if ($line == $lineCount) class="inactive" @endif type="submit" value="↓"> --}}
                {{-- <input type="submit" value="↓"> --}}
                <input @if ($templates->where('line', $line)->max('position') == 1 && $templates->where('line',
                $line+1)->count() == 0) class="inactive" @endif type="submit" value="↓">
            </form>
        </div>
        <div class="buttons edit-buttons">
            <p>id: {{ $template->id }}</p>
            <form class="edit" action="/edittemplate" method="get">
                @csrf
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="position" value="{{ $position }}">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input type="hidden" name="templateid" value="{{ $template->id }}">
                <input type="submit" value="✎">
            </form>
            <form class="remove" action="/deletetemplate" method="get">
                @csrf
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="position" value="{{ $position }}">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input type="hidden" name="templateid" value="{{ $template->id }}">
                <input type="submit" value="x">
            </form>
        </div>

    @else
        {{-- @if ($lastIsEmpty === true) --}}
        @if ($position == $plusPosition)
            <form class="plus" action="/newtemplate" method="get">
                @csrf
                <input type="hidden" name="line" value="{{ $line }}">
                <input type="hidden" name="position" value="{{ $position }}">
                <input type="hidden" name="productid" value="{{ $productId }}">
                <input type="submit" value="+">
            </form>
        @endif
        {{-- @endif --}}
    @endif
</div>

@endfor
</div>
@endfor

</div>
<button class="newLine" onclick="newLine()">Добавить линию</button>
<script>
    // рендерит новую линию элементов с плюсом
    function newLine() {
        let lines = document.querySelectorAll('.line')
        let lastLine = Number(lines[lines.length - 1].getAttribute('line'))

        let templateCount = lines[0].querySelectorAll('.template').length //кол-во пустых блоков в строке
        let plusElem = document.createElement('form')
        plusElem.className = 'plus'
        plusElem.action = '/newtemplate'
        plusElem.method = 'get'
        let html = ''
        html += '<input type="hidden" name="_token" value="' + document.querySelector('input[name="_token"]').value +
            '">'
        html += '<input type="hidden" name="line" value="' + Number(lastLine + 1) + '">'
        html += '<input type="hidden" name="position" value="1">'
        html += '<input type="hidden" name="productid" value="' + document.querySelector('input[name="productid"]')
            .value + '">'
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
