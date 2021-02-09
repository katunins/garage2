<link rel="stylesheet" href="css/calendar.css">
<link rel="stylesheet" href="css/general.css">

{{-- $Users, $Date, $Tasks --}}

<div class="head-block">

    <a class="to-main-page" href="/"></a>
    <div class="date-block">
        @if ($calendarDays == 1)<div class="date-title">

            {{ $rusDate }}
    </div>@endif
    <input type="hidden" name="date" value="">
    <div class="buttons">
        <form action="/calendar" method="get">
            <input type="submit" value="- @if ($calendarDays == 1) День @else Неделя @endif">
            @php
                if ($calendarDays == 1) $Date->subDay(); else $Date->subWeek();
            @endphp
            <input type="hidden" name="date" value="{{ $Date->toDateString() }}">
            {{-- что бы не потерять другие настройки GET --}}
            @foreach ($_GET as $key=>$value)
                @if ($key !='date') <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endif
                @endforeach
        </form>
        <form action="/calendar" method="get">
            <input type="submit" value="Сегодня">
            <input type="hidden" name="date" value="{{ $today->toDateString() }}">
            {{-- что бы не потерять другие настройки GET --}}
            @foreach ($_GET as $key=>$value)
                @if ($key !='date') <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endif
                @endforeach
        </form>
        <form action="/calendar" method="get">
            <input type="submit" value="+ @if ($calendarDays == 1) День @else Неделя @endif">
            @php
                // поправим Date на неделю или день назад. Она изменилась в шапке при создании кнопки
                if ($calendarDays == 1) $Date->addDays(2); else $Date->addWeeks(2);
            @endphp
            <input type="hidden" name="date" value="{{ $Date->toDateString() }}">
            {{-- что бы не потерять другие настройки GET --}}
            @foreach ($_GET as $key=>$value)
                @if ($key !='date') <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endif
                @endforeach
        </form>
        @php
            if ($calendarDays == 7) $Date->subWeek(); else $Date->subDay();
        @endphp
    </div>
    <div class="list-option">
        <br>
        <form action="/calendar" method="get">
            <input type="hidden" name="calendarstyle" value={{ $calendarStyle==1?0:1 }}>
            <input id="calendar-style" type="checkbox"
                {{ $calendarStyle==1?'checked':'' }}
                onclick="this.parentNode.submit()">
            <label for="calendar-style">Отображение списком</label>
        </form>
        <form action="/calendar" method="get">
            <input type="hidden" name="calendardays" value={{ $calendarDays==1?7:1 }}>
            <input id="calendar-days" type="checkbox"
                {{ $calendarDays==7?'checked':'' }}
                onclick="this.parentNode.submit()">
            <label for="calendar-days">Календарь за неделю</label>
        </form>
    </div>
</div>
<div class="filter-block">

    <form action="/calendar" method="get">
        <label for="filterdealname">Фильтр сделки</label>
        <input type="text" name="filterdealname" value="{{ $filterDealName }}" size="10">
        {{-- что бы не потерять другие настройки GET --}}
        @foreach ($_GET as $key=>$value)
            @if ($key !='filterdealname') <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endif
            @endforeach
    </form>

    <form action="/calendar" method="get">
        <label for="gridinhour">Масштаб</label>
        <input type="text" name="gridinhour" value="{{ $gridInHour }}" size="3">
        {{-- что бы не потерять другие настройки GET --}}
        @foreach ($_GET as $key=>$value)
            @if ($key !='gridinhour') <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endif
            @endforeach
    </form>


</div>


<div class="">
    <div class="status-filter-title">Фильтр по стадиям</div>
    <div class="status-filter">
        @foreach ([
            'temp'=>'Временные',
            'wait'=>'Новые',
            'repair'=>'В ремонте',
            'finished'=>'Завершены',
            ] as $key=>$item)
            @if ($calendarStyle == 1)
                <form action="/calendar" method="get">
                    <input type="hidden" name="status-{{ $key }}"
                        value={{ !$statusFilter['status-'.$key] }}>
                    <input class="status-filter-buttons task-status-{{ $key }}" type="submit"
                        value="{{ $statusFilter['status-'.$key]?'✓':'   ' }} {{ $item }}">
                </form>
            @else
                <input class="status-filter-buttons task-status-{{ $key }}" type="button"
                    data-status="status-{{ $key }}"
                    data-checked={{ $statusFilter['status-'.$key]?1:0 }}
                    value="{{ $statusFilter['status-'.$key]?'✓':'   ' }} {{ $item }}"
                    onclick="changeGroupFilter()">
            @endif
        @endforeach
    </div>
</div>
</div>
@for ($day = 0; $day < $calendarDays; $day++)
    @php
        // Отфильтруем задачи на один день из недели, для случая если это календарь недели
        $weekCalendarDay = clone $Date;
        $weekCalendarDay->addDay($day);
        $weekCalendarDay->setTime(0,0,1);

        $weekEndTimeFilter = clone $weekCalendarDay;
        $weekEndTimeFilter->setTime(23,59,59);

        if ($calendarDays == 7)
        {
        echo '<br>
        <div class="date-title">'.\App\Http\Controllers\CalendarController::getRusDate($weekCalendarDay).'</div>';
        }
    @endphp

    <div class="calendar-block" @if ($calendarStyle==0) style="grid-template-columns: 60px repeat({{ $Users->count() }}, 240px); 
    grid-template-rows: 1fr repeat({{ $gridRowCount }}, {{ $scale }}px);" @endif>

        @if ($calendarStyle==0)
            {{-- заголовок --}}
            @foreach ($Users as $key => $item)
                @php
                    // пометим колонку определенным юзером
                    $userColumn[$item->id] = ($key+2).'/'.($key+3);

                @endphp
                <div class="linehead" style="grid-row: 1/2; grid-column: {{ $userColumn[$item->id] }};">
                    {{ $item->name }}
                </div>
            @endforeach
        @endif
        {{-- Задачи --}}

        @foreach ($Tasks->whereBetween('start', [$weekCalendarDay, $weekEndTimeFilter]) as $item)

            @php

                // подготовим тектс для модального окна
                $modalMessage = '';
                foreach ($item->getAttributes() as $param => $value) {
                if ($param == 'master') $value = $Users->find($value)->name;

                $skip = false;
                foreach (['templateid', 'status', 'deal', 'created_at', 'updated_at', 'startGrid', 'endGrid'] as $el) {
                if ($param == $el) $skip = true;
                }

                if (!$skip) $modalMessage .='<b>'.$param.'</b>'.' '.$value.'<br>';
                }

                if ($calendarStyle!=0) $tasksToDelete[]=$item->id;
            @endphp


            <div class="task task-status-{{ $item->status }}" @if ($calendarStyle==0)
                style="grid-row: {{ $item->startGrid }}/{{ $item->endGrid }}; grid-column: {{ $userColumn[$item->master] }}"
            @else style="width: 600px; height: 40px;" @endif
            onclick="modal('open', '{{ $item->deal }} - {{ $item->generalinfo }}','{{ $modalMessage }}', {name:
            `ok`,
            function: ()=>{modal(`close`)}})">

            <div @if ($calendarStyle!=0) style="margin-left: 25px" @endif class="title"><span
                    class="dealname">{{ $item->deal }}</span>{{ $item->name }}
                @if ($calendarStyle!=0)
                    {{ $Users->find($item->master)->name }}@endif</div>
            <div @if ($calendarStyle!=0) style="margin-left: 25px" @endif class="taskname">{{ $item->generalinfo }}
            </div>

    </div>
    <input class="task-checkbox" type="checkbox" onchange="changeIDtodelete({{ $item->id }})">

@endforeach

@if ($calendarStyle==0)
    {{-- Сетка --}}
    {{-- начальная 1/2 линия --}}
    @php
        $rowStart = 2;
        $rowEnd =$gridInHour*$beforeAfter+2;
    @endphp
    @for ($column = 1; $column < $Users->count()+2; $column++)
        <div class="hour-line"
            style="grid-row: {{ $rowStart }}/{{ $rowEnd }}; grid-column: {{ $column }}/{{ $column+1 }}">
            @if ($column == 1)
                <div class="time-tag">
                    {{ floor($workTimeStart-$beforeAfter) }} : 30
                </div>
            @endif
        </div>
    @endfor
    {{-- часовые ячейки --}}
    @for ($row = 0; $row <= $workTimeEnd-$workTimeStart; $row ++) @php $rowStart=$gridInHour*$beforeAfter+2 +
        $row*$gridInHour; $rowEnd=$gridInHour*$beforeAfter+2 + ($row+1)*$gridInHour; @endphp @for ($column=1;
        $column < $Users->count()+2; $column++)

            <div class="hour-line"
                style="grid-row: {{ $rowStart }}/{{ $rowEnd }}; grid-column: {{ $column }}/{{ $column+1 }}">
                @if ($column == 1)
                    <div class="time-tag">
                        {{ $workTimeStart+$row }} : 00
                    </div>
                @endif
            </div>
    @endfor

@endfor

{{-- конечная 1/2 линия --}}
@php
    $rowStart = $gridInHour*$beforeAfter+2 + ($row)*$gridInHour;
    $rowEnd = $rowStart+$gridInHour/2;
@endphp
@for ($column = 1; $column < $Users->count()+2; $column++)
    <div class="hour-line"
        style="grid-row: {{ $rowStart }}/{{ $rowEnd }}; grid-column: {{ $column }}/{{ $column+1 }}">
        @if ($column == 1)
            <div class="time-tag">
                {{ round($workTimeEnd) }} : 30
            </div>
        @endif
    </div>
@endfor
@endif

</div>

</div>

@endfor

@if ($calendarStyle!=0 )
    {{-- && isset($tasksToDelete) !==false --}}
    <form class="erase-all-button hide" action="/deletealltasks" method="GET">
        <input id="json-to-delete" type="hidden" name="taskstodelete">
        {{-- value={{ json_encode($tasksToDelete) }} --}}
        <input type="hidden" name="time" value={{ time() }}>
        <input type="submit" value="Удалить все выбранные задачи?">
    </form>
@endif

<div class="footer-block"></div>
<div id="modal" class="hide">
    <div class="modal-container">
        <div class="modal-title hide"></div>
        <div class="modal-text hide"></div>
        <div class="modal-buttons hide">
            <button class="modal-button1 hide"></button>
            <button class="modal-button2 hide"></button>
        </div>
        <button class="modal-close-button" onclick="modal('close')">✕</button>
    </div>
</div>
<script src="js/general.js"></script>
<script>
    function changeGroupFilter() {
        let elem = event.target
        console.log(elem.getAttribute('data-checked'))
        if (elem.getAttribute('data-checked') == 1) {
            // выключаем
            elem.setAttribute('data-checked', 0)
            elem.value = '   Временные'
            document.querySelectorAll('.task.task-' + elem.getAttribute('data-status')).forEach(el => {
                el.classList.add('half-opacity')
            })
        } else {
            // включаем
            elem.setAttribute('data-checked', 1)
            elem.value = '✓ Временные'
            document.querySelectorAll('.task.task-' + elem.getAttribute('data-status')).forEach(el => {
                if (el.classList.contains('half-opacity')) el.classList.remove('half-opacity')
            })
        }
    }

    function changeIDtodelete(taskId) {
        let status = event.target.checked
        let idArr = []
        document.querySelectorAll('.task-checkbox:checked').forEach(el=>idArr.push(taskId))
        document.getElementById('json-to-delete').value = JSON.stringify(idArr)
        let eraseAllButton = document.querySelector('.erase-all-button')
        if (idArr.length>0 && eraseAllButton.classList.contains('hide')) eraseAllButton.classList.remove('hide'); else eraseAllButton.classList.add('hide');
    }

</script>
