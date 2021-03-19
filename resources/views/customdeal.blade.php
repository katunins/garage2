<link rel="stylesheet" href="/css/newtemplate.css">
<link rel="stylesheet" href="/css/general.css">

<h1>
    <a class="to-main-page" href="/"></a>
    Новая нестандартная задача
</h1>

<form action="/newcustomtask" method="POST" onsubmit="formSubmit()">
    @csrf
    <div class="input-blocks">
        <div class="input-group">
            <div class="param-input-help">
                <div>
                    <label for="startdate">Дата</label>
                    <input class="mini-size no-empty" type="date" id="startdate" name="startdate" placeholder="10">
                </div>
                <div>
                    <label for="starttime">Время</label>
                    <input class="mini-size no-empty" type="time" id="starttime" name="starttime" placeholder="10">
                </div>
                {{-- <div>
                    <label for="dealid">ID сделки</label>
                    <input class="mini-size no-empty" type="text" id="dealid" name="dealid" placeholder="10">
                </div> --}}
                <input type="hidden" name="dealid" value="{{ $dealid }}">
            </div>
            <div>
                <label>Основные параметры</label>
                <input class="max-size no-empty" name="generalparams" placeholder="Фотокнига, Формат 20х20">
            </div>
            <p class="help">В этом блоке необохдимо указать общие парамеры и время старта группа задач</p>
        </div>
        <input type="hidden" class="task-item">

        <datalist id="masters">
            @foreach ($Users as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </datalist>
    </div>

    <div class="input-group">
        <button type="button" class="button" onclick="buidNewBlock()">Добавить задачу</button>
    </div>

    <div class="buttons">
        <a href="{{ url()->previous() }}">Назад</a>
        <input type="submit" value="Сохранить">
    </div>

</form>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        buidNewBlock()
    })

    function formSubmit() {
        let noEmptyElems = document.querySelectorAll('.no-empty')
        let noEmptyInputs = 0;
        noEmptyElems.forEach(el => {
            if (!el.value) {
                el.classList.add('empty-alert')
                noEmptyInputs++
            }
        })
        if (noEmptyInputs > 0) {
            event.preventDefault()
        }
    }

    function deleteInput() {
        event.target.parentNode.querySelector('input').value = ''
    }

    function deleteTask(id) {
        let elem = document.querySelector(`.task-item[itemid="${id}"]`)
        elem.parentNode.removeChild(elem);
    }

    function buidNewBlock() {
        let inputElems = document.querySelectorAll('.task-item')
        let lastInputElem = inputElems[inputElems.length - 1]
        let lastId = lastInputElem.getAttribute('itemid')
        let id = lastId === null ? 1 : Number(lastId) + 1

        let innerHtml = ''
        innerHtml += `<div class="custom-task-number"><span>${id}</span></div>`

        innerHtml += '<div class="param-input-help">'

        innerHtml += '<div>'
        innerHtml += `<label for="masterid[${id}]">ID мастера</label>`
        innerHtml += `<input class="no-empty" name="masterid[${id}]" list="masters" placeholder="Выберете мастера">`
        innerHtml += '<button type="button" onclick="deleteInput()">×</button>'
        innerHtml += '</div>'

        innerHtml += '<div>'
        innerHtml += `<label for="producttime[${id}]">Время, мин.</label>`
        innerHtml += `<input class="mini-size no-empty" type="text" name="producttime[${id}]" placeholder="10">`
        innerHtml += '</div>'

        innerHtml += '<div>'
        innerHtml += `<label for="bufer[${id}]">Буффер, мин.</label>`
        innerHtml += `<input class="mini-size" type="text" name="bufer[${id}]" placeholder="1440">`
        innerHtml += '</div>'

        innerHtml += '</div>'

        innerHtml += '<div>'
        innerHtml += `<label for="taskname[${id}]">Название задачи</label>`
        innerHtml +=
            `<input class="max-size no-empty" type="text" name="taskname[${id}]" placeholder="Подготовка картона">`
        innerHtml += '</div>'

        innerHtml += '<div>'
        innerHtml += `<label for="miniparams[${id}]">Мини-параметры</label>`
        innerHtml += `<input class="max-size" name="miniparams[${id}]" placeholder="Черный форзац, калька">`
        // innerHtml +=
        //     '<p class="help">Параметры, которые будет видеть мастер в задаче, не открывая ее. Основные параметры (номер заказа и название продукта указывать не нужно, так как они присутсвуют в задаче по умолчанию)</p>'
        innerHtml += '</div>'

        innerHtml += '<div>'
        innerHtml += `<br><button type="button" onclick="deleteTask(${id})">Удалить задачу</button>`
        innerHtml += '</div>'

        let inputGroup = document.createElement('div')
        inputGroup.className = 'input-group task-item'
        inputGroup.setAttribute('itemid', id)
        inputGroup.innerHTML = innerHtml

        lastInputElem.parentNode.insertBefore(inputGroup, lastInputElem.nextSibling);
    }

</script>
