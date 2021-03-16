<link rel="stylesheet" href="css/welcome.css">
<link rel="stylesheet" href="css/general.css">
@csrf

<div class="container">
    <h1>korobook Администратор</h1>
    <div class="container">
        <div class="menu">
            <li><a href="/customtask">Создать задачу</a></li>
            <br>
            <li><a href="/templates">Шаблоны задач</a></li>
            <li><a href="/masters">Список мастеров</a></li>
            <li><a href="/checkDeadline" target="blank">Проверить Deadline сделок</a></li>
            <li><a href="/calendar">Календарь</a></li>
            <br>
            <li><a href="http://fixtome.ru/research/Z5VKIU3xtynTR2wKPIrb8yri5P4WB2LSqumMSyMcqH">Посоветуй что добавить в
                    приложение</a></li>
        </div>
        <br>
        <div class="">
            <form action="/deal2tasks" method="GET">
                {{-- @csrf --}}

                @error ('deal')
                    <p>{{ $message }}</p>
                @enderror
                <label for="id">ID сделки в Битрикс24</label>
                <input type="text" id="id" name="id">
                <input type="checkbox" name="log" id="log">
                <label for="log">Логирование</label>
                <input type="checkbox" name="startnow" id="startnow">
                <label for="startnow">В ближайшее время</label>
                <p><input type="submit" name="" value="Создать"></p>
            </form>
        </div>
        <div class="dashboard">
            <div>
                <h2>Сделки под угрозой срыва срока</h2>
                <ul class="stuck-deals">
                    @foreach ($Stuck as $item)
                        <li class="block-right-button task-status-{{ $item->type }}">
                            <span class="dealname">{{ $item->deal }}</span>
                            <span>
                                Проблема в задаче {{ $item->task }}
                                <form action="/removestuck" method="get">
                                    @csrf
                                    <input type="hidden" name="stuckid" value="{{ $item->id }}">
                                    <input class="right-button" type="submit" value="Удалить">
                                </form>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2>Просроченные задачи</h2>
                <ul class="over-tasks">
                    @foreach ($overTasks->sortBy('master') as $itemTask)
                        <li class="task-status-{{ $itemTask->status }}"
                            onclick="modalFromTask({{ json_encode($itemTask) }})">
                            <span class="avatar"
                                style="background-image: url({{ $Users->find($itemTask->master)->avatar ?? '' }})"></span>
                            <span>{{ $itemTask->name }}</span>
                            <span>{{ $itemTask->end }}</span>
                            <span class="dealname">{{ $itemTask->deal }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @include ('modal')
</div>
<script src="js/general.js"></script>
