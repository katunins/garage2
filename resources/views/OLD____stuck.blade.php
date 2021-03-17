<link rel="stylesheet" href="css/welcome.css">
<link rel="stylesheet" href="css/general.css">

<div class="container">
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
    </div>
</div>
