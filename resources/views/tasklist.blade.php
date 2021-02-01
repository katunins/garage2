<link rel="stylesheet" href="css/tasklist.css">

<div class="container">
    <div class="filters">

        <div class="filter-block">
            <h4>Мастера</h4>
            @foreach ($tasks->unique('master') as $item)
            @php
            $masterName = $users->where('id', $item->master)->first();
            @endphp
            <li>
                <input type="checkbox" name="master" value={{ $item->master }} onchange="reFilter()">
                <label for="master">{{ $masterName ? $masterName->name : 'id '.$item->master}}</label>
            </li>
            @endforeach
        </div>

    </div>
    <div class="board"></div>
</div>

<script>
    // Измене фильтр в левой панели
    function reFilter () {
        console.log ('reFilter', event.target)
    }
</script>