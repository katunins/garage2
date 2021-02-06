<link rel="stylesheet" href="css/calendar.css">
<link rel="stylesheet" href="css/general.css">

{{-- $Users, $Date, $Tasks --}}

<div class="head-block">

    <a class="to-main-page" href="/">←</a>
    <div class="date-block">
        <div class="date-title">

            {{ $rusDate }}</div>
        <input type="hidden" name="date" value="">
        <div class="buttons">
            <form action="/calendar" method="get">
                <input type="submit" value="- День">
                <input type="hidden" name="date" value="{{ $Date->subDay()->toDateString() }}">
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
                <input type="submit" value="+ День">
                <input type="hidden" name="date" value="{{ $Date->addDays(2)->toDateString() }}">
                {{-- что бы не потерять другие настройки GET --}}
                @foreach ($_GET as $key=>$value)
                @if ($key !='date') <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endif
                @endforeach
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
            <form action="/calendar" method="get">
                <input type="hidden" name="status-{{ $key }}" value={{ !$statusFilter['status-'.$key]}}>
                <input class="status-filter-buttons task-status-{{ $key }}" type="submit"
                    value="{{ $statusFilter['status-'.$key]?'✓':'   ' }} {{ $item }}">
            </form>
            @endforeach
        </div>
    </div>
</div>

<div class="calendar-block" style="grid-template-columns: 60px repeat({{ $Users->count() }}, 240px); 
    grid-template-rows: 1fr repeat({{ $gridRowCount }}, {{ $scale }}px);">



    {{-- заголовок --}}
    @foreach ($Users as $key => $item)
    @php
    // пометим колонку определенным юзером
    $userColumn[$item->id] = ($key+2).'/'.($key+3);
    @endphp
    <div class="linehead" style="grid-row: 1/2; grid-column: {{ $userColumn[$item->id] }};">
        {{-- <input type="hidden" name="user-id-{{ $item->id }}"
        value="{{ $userColumn[$item->id] }}">--}}
        {{ $item->name }}
    </div>
    @endforeach

    {{-- Задачи --}}

    @foreach ($Tasks as $item)
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
    @endphp
    <div class="task task-status-{{ $item->status }}"
        style="grid-row: {{ $item->startGrid }}/{{ $item->endGrid }}; grid-column: {{ $userColumn[$item->master] }}"
        onclick="modal('open', '{{ $item->deal }} - {{ $item->generalinfo }}','{{ $modalMessage }}', {name: `ok`, function: ()=>{modal(`close`)}})">

        <div class="title"><span class="dealname">{{ $item->deal }}</span>{{ $item->name }}</div>
        <div class="taskname">{{ $item->generalinfo }}</div>
    </div>
    @endforeach

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


</div>

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
    document.addEventListener('DOMContentLoaded', function(){
        // // получим задачи по фильтру
        // window.filter = {
        //     master: document.querySelectorAll('.master-filter:checked'),
        //     dealname: document.getElementById('dealname').value,

        // }
        
    })
</script>