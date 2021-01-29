{{-- $templates --}}
{{-- $lineCount --}}
{{-- $productId --}}

<link rel="stylesheet" href="/css/board.css">
@php
    $positionCount = 30;
@endphp
<div class="lineBlock">

    @for ($line = 1; $line <= $lineCount; $line++)
        <div class="line" line={{ $line }}>

            @php
                //$lastIsEmpty = true; //предыдущий Template пустой, в нем стоит плюс
                // определим позицию элемента Plus
                $lastElemInLine = $templates->where('line', $line)->max('position');
                $plusPosition = $lastElemInLine? $lastElemInLine+1:1;
            @endphp

            @for ($position=1; $position <=$positionCount; $position++)

                @php
                    $template=$templates->where('line', $line)->where('position', $position)->first();
                @endphp

                @if ($template && $template->taskidbefore)
                    @php
                        $prevTemplate = $templates->where('id', $template->taskidbefore)->first();
                        $emptyCount = $prevTemplate?$prevTemplate->position+1-$position:0;
                    @endphp
                    @if ($emptyCount >0)
                        @for ($x = 0; $x < $emptyCount; $x++)
                            <div class="template">
                            </div>
                        @endfor
                    @endif
                @endif
                <div class="template">


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
                        <div class="buttons">
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
