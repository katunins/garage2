<link rel="stylesheet" href="css/calendar.css">

{{-- $Users, $Date, $Tasks --}}

<div class="head-block">
    <div class="date-block">
        <div id="date-text">{{ $rusDate }}</div>
        <input type="hidden" name="date" value="">
        <div class="buttons">
            <a href="calendar?date={{ $Date->subDay()->toDateString() }}">- День</a>
            <a href="calendar">Сегодня</a>
            <a href="calendar?date={{ $Date->addDay()->toDateString() }}">+ День</a>
        </div>
    </div>
    <div class="period-block">
        Период
        <div class="buttons">
            <button class="active-button">День</button>
            <button>Неделя</button>
            <button>Месяц</button>
        </div>
    </div>
    <div class="deal-filter-block">
        <label for="dealname">Введите название сделки</label>
        <input type="text" name="dealname" id="dealname">
    </div>
    <div class="master-filter-block">
        <button>Выбрать всех</button>
        @foreach ($Users as $item)

            <input class="master-filter" type="checkbox" name="user-{{ $item->id }}" id="user-{{ $item->id }}"
                value="{{ $item->id }}" checked>
            <label for="user-{{ $item->id }}">{{ $item->name }}</label>

        @endforeach
    </div>
</div>

<div class="calendar-block" style="grid-template-columns: 60px repeat({{ $Users->count() }}, 240px); 
grid-template-rows: 30px repeat({{ $gridRowCount }}, {{ $scale }}px);">



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
        <div class="task" status="{{ $item->status }}"
            style="grid-row: {{ $item->startGrid }}/{{ $item->endGrid }}; grid-column: {{ $userColumn[$item->master] }}">

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
    @for ($row = 0; $row <= $workTimeEnd-$workTimeStart; $row ++)
        @php
            $rowStart = $gridInHour*$beforeAfter+2 + $row*$gridInHour;
            $rowEnd = $gridInHour*$beforeAfter+2 + ($row+1)*$gridInHour;
        @endphp
        @for ($column = 1; $column < $Users->count()+2; $column++)

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




    {{-- <div class="time">9 мин</div> --}}
    {{-- <div class="task-buttons">
                <button>✓</button>
                <button>?</button>
                <button>✕</button>
            </div> --}}

</div>

<div class="footer-block"></div>

{{-- <script src="js/general.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        // получим задачи по фильтру
        window.filter = {
            master: document.querySelectorAll('.master-filter:checked'),
            dealname: document.getElementById('dealname').value,

        }
    })
</script> --}}
