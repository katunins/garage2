<link rel="stylesheet" href="css/general.css">
<link rel="stylesheet" href="css/tasklist.css">
@csrf

<div class="head">
    <div class="filter-block">
        <div class="dealname-filter">
            <div>
                <input type="text" class="text-filter input-filter" name="deal" equality='like' exact=false
                    value={{ $_GET['dealname-filter'] ?? '' }}>
                <label for="dealname-filter">Название сделки или задачи</label>
                <button class="input-reset" onclick="inputReset()">x</button>
            </div>
            <div>
                <input type="text" class="text-filter input-filter" name="master" equality='=' exact=true
                    value={{ $_GET['master-filter'] ?? '' }}>
                <label for="master-filter">ID мастера</label>
                <button class="input-reset" onclick="inputReset()">x</button>
            </div>
            <div>
                @foreach ([['status'=>'wait', 'name'=>'В ожидании'], ['status'=>'finished', 'name'=>'Завершены']] as $item)
                    @php
                        $checked = (isset($_GET['status-'.$item['status']]) && $_GET['status-'.$item['status']]==='1')?
                        true :false;
                    @endphp
                    <li>
                        <input type="checkbox" class="checkbox-filter input-filter" equality='=' exact=true
                            id="status-{{ $item['status'] }}" name="status" @if ($checked)
                            checked @endif value="{{ $item['status'] }}">
                        <label
                            for="status-{{ $item['status'] }}">{{ $item['name'] }}</label>

                    </li>
                @endforeach
            </div>
        </div>
        <div class="status-filter"></div>
        <div class="master-filter"></div>
        <div class="date-filter"></div>
    </div>
</div>
<div class="container">
    <div class="tasks-block" id="tasks-block">

    </div>
    <div class="count-block"><span id="tasks-count"></span></div>
</div>

@include ('modal')

<script>
    // удаляет первый input в родителе
    function inputReset() {
        event.target.parentNode.querySelector('input').value = ''
    }


    // создает объект из всех не пустых фильтров
    function getFilter() {
        let filterData = []
        document.querySelectorAll('.text-filter').forEach(el => {
            if (el.value) filterData.push({
                param: el.name,
                equality: el.getAttribute('equality'),
                value: el.getAttribute('exact') === true ? el.value : '%' + el.value + '%'
            })

        })

        document.querySelectorAll('.checkbox-filter').forEach(el => {

            if (el.checked) filterData.push({
                param: el.name,
                equality: el.getAttribute('equality'),
                value: el.value
            })

        })
        console.log(filterData)
        return filterData
    }

    // показывает загрузчик
    function loader(type) {
        return
    }

    // загружает задачи по АПИ и ренедерит
    function loadTasks() {
        loader(true)
        let filterData = getFilter()
        fetch('/api/getcalendar', {

                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json, text-plain, */*",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                },
                method: "post",
                credentials: "same-origin",
                body: JSON.stringify({
                    type: 'filterData',
                    filterData: filterData
                }),
            })
            .then((response) => response.json())
            .then((response) => {
                if (response) render(response.tasks)
            })
            .catch(function (error) {
                console.log(error);
            })
            .finally(() => loader(false))
    }

    // возвращает HTML элемента задачи
    function getTaskElem(task) {
        // console.log (task)
        let html = ''
        html += `<div class="deal">${task.deal}</div>`
        html += `<div class="name">${task.name}</div>`
        html += `<div class="avatar task-avatar" style="background-image: url(${task.masteravatar})"></div>`

        return html
    }

    // рендерит задачи
    function render(tasks) {
        // console.log('render', tasks)
        let container = document.getElementById('tasks-block')
        container.innerHTML = ''

        let day = 32
        tasks.forEach(task => {
            let start = new Date(task.start)
            if (start.getDate() !== day) {
                day = start.getDate()
                let dateElem = document.createElement('div')
                dateElem.className = `date`
                dateElem.innerHTML = start.getDate() + '.' + start.getMonth()
                container.appendChild(dateElem)
            }
            let elem = document.createElement('li')
            elem.className = `task-elem task-status-${task.status}`
            elem.onclick = () => {
                modalFromTask(task)
            }
            elem.innerHTML = getTaskElem(task)
            container.appendChild(elem)
            return
        })
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadTasks()
        document.querySelectorAll('.input-filter').forEach(el => el.addEventListener('input', loadTasks))
    })

</script>

<script src="js/general.js"></script>
