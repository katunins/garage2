<link rel="stylesheet" href="css/general.css">
<link rel="stylesheet" href="css/tasklist.css">
@csrf
<h1>
    <a class="to-main-page" href="/"></a>
    Список задач</h1>
<div class="head">
    <div class="filter-block">
        <div class="dealname-filter">
            <div>
                <input type="text" class="text-filter input-filter" name="deal" equality='like' exact='false'
                    value={{ $_GET['dealname-filter'] ?? '' }}>
                <label for="dealname-filter">Название сделки или задачи</label>
                <button class="input-reset" onclick="inputReset()">x</button>
            </div>
            <div>
                <input type="text" class="text-filter input-filter" name="master" equality='=' exact='true'
                    value={{ $_GET['master-filter'] ?? '' }}>
                <label for="master-filter">ID мастера</label>
                <button class="input-reset" onclick="inputReset()">x</button>
            </div>
            <div>
                @php
                    $statusArr = [
                    ['param'=>'status','name'=>'В ожидании', 'value'=>'wait', 'checked'=>true],
                    ['param'=>'status','name'=>'Завершены', 'value'=>'finished', 'checked'=>false],
                    ['param'=>'stuck','name'=>'Задачи, остановленные мастером', 'value'=>true, 'checked'=>false],
                    ];
                @endphp
                @foreach ($statusArr as $item)
                    @php
                        $checked = isset($_GET[$item['param'].'-'.$item['value']]) || $item['checked'];
                    @endphp
                    <li>
                        <input type="checkbox" class="checkbox-filter input-filter"
                            id={{ $item['param'].'-'.$item['value'] }}
                            name={{ $item['param'] }}
                            value={{ $item['value'] }} @if ($checked) checked @endif>
                        <label
                            for={{ $item['param'].'-'.$item['value'] }}>{{ $item['name'] }}</label>
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
<div class="hide" id="loader">
        <img src="/images/7plQ.gif" alt="">
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
                text: {
                    param: el.name,
                    equality: el.getAttribute('equality'),
                    value: el.getAttribute('exact') === 'true' ? el.value : '%' + el.value + '%'
                }
            })

        })

        let filterCheckbox = {}
        document.querySelectorAll('.checkbox-filter').forEach(el => {
            if (el.checked) {
                if (!filterCheckbox[el.name]) filterCheckbox[el.name] = []
                filterCheckbox[el.name].push(el.value)
            }
        })
        filterData.push({
            checkbox: filterCheckbox
        })
        return filterData
    }

    // показывает загрузчик
    function loader(type) {
        let loaderElem = document.getElementById('loader')
        if (type) loaderElem.className=''; else loaderElem.className='hide';
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
        html += '<div class="flex-line">'
        html += `<div class="deal">${task.deal}</div>`
        if (typeof task.stuck !== 'undefined')  html += `<div class="stuck-icon">i</div>`
        html += `<div class="name">${task.name}</div>`
        html += '</div>'

        html += `<div class="start">${task.start.slice(11, 16)}</div>`
        html += `<div class="avatar task-avatar" style="background-image: url(${task.masteravatar})"></div>`
        if (typeof task.stuck !== 'undefined') {
            html += '<div class="flex-line">'
            // html += `<div class="stuck-icon">i</div>`
            html +=
                `<div class="stuck-message">${task.stuck.mastername}, ${task.stuck.task.name}, ${task.stuck.comment}</div>`
            html += '</div>'
        }

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
                dateElem.innerHTML = start.getDate() + '.' + Number(start.getMonth()+1)
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
