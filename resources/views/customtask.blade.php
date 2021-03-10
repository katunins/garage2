<link rel="stylesheet" href="css/newtemplate.css">
<link rel="stylesheet" href="css/general.css">

<h1>
    <a class="to-main-page" href="/"></a>
    Новая задача
</h1>

<form action="/savetemplate" method="POST">
    @csrf
    <div class="input-blocks">
        <div class="input-group wide-input-block">
            <div class="param-input-help">
                <div>
                    <label for="productdate">Дата</label>
                    <input class="mini-size" type="date" id="productdate" name="productdate"
                        value="{{ old('productdate', '') }}" placeholder="10">
                </div>
                <div>
                    <label for="producttime">Время</label>
                    <input class="mini-size" type="time" id="producttime" name="producttime"
                        value="{{ old('producttime', '') }}" placeholder="10">
                </div>
            </div>
            <div>
                <label> Основные параметры</label>
                <input class="max-size" name="generalparams" placeholder="Фотокнига, Формат 20х20"
                    value="{{ old('generalparams') ?? ($template->generalparams ?? '') }}">
            </div>
        </div>
        <div class="input-group wide-input-block">

            <div class="param-input-help">
                <div>
                    @error('masterid')
                    <p class="alert">{{ $message }}</p>
                    @enderror
                    <label for="masterid">ID мастера</label>
                    <input name="masterid" list="masters" placeholder="Выберете мастера"
                        value="{{ old('masterid') ?? ($template->masterid ?? '') }}">
                    <button type="button" onclick="this.parentNode.querySelector('input').value=''">×</button>
                </div>
                <div>
                    @error('producttime')
                    <p class="alert">{{ $message }}</p>
                    @enderror
                    <label for="producttime">Длит-сть, мин.</label>
                    <input class="mini-size" type="text" id="producttime" name="producttime"
                        value="{{ old('producttime', $template->producttime ?? '') }}" placeholder="10">
                </div>
            </div>

            <datalist id="masters">
                @foreach ($Users as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </datalist>

            <div>
                @error('taskname')
                <p class="alert">{{ $message }}</p>
                @enderror
                <label for="taskname">Название задачи</label>
                <input class="max-size" type="text" id="taskname" name="taskname"
                    value="{{ old('taskname', $template->taskname ?? '') }}" placeholder="Подготовка картона">
            </div>

            <div>
                <label> Мини-параметры</label>
                <input class="max-size" name="miniparams" placeholder="Черный форзац, калька"
                    value="{{ old('miniparams') ?? ($template->miniparams ?? '') }}">
                <p class="help">Параметры, которые будет видеть мастер в задаче, не открывая ее. Основные параметры
                    (номер
                    заказа и название продукта указывать не нужно, так как они присутсвуют в задаче по умолчанию)</p>

            </div>
        </div>
    </div>

    <div class="buttons">
        <a href="{{ url()->previous() }}">Назад</a>
        <input type="submit" value="Сохранить">
    </div>

</form>
<script>
    function helpTimeCalc() {
        let productTime = Number(document.getElementById('producttime').value)
        let paramTime = Number(document.getElementById('paramtime').value)
        document.querySelectorAll('.help-time-calc').forEach(el => {
            console.log(el)
            let square = Number(el.getAttribute('data-square'))
            el.innerHTML = Math.round(productTime + paramTime / 60 * square)
        })
    }

    function setHelpValues(elemId) {
        let valuesArr = []

        document.querySelectorAll('input[name="' + event.target.parentNode.querySelector('.condition').value + '"]')
            .forEach(el => valuesArr.push(el.value))
        html = ''
        valuesArr.forEach(el => {
            html += '<button '
            console.log()
            if (event.target.parentNode.querySelector('.param-input').value.indexOf(el) != -1) html +=
                'class="active-help-button" ';
            html += 'type="button" onclick="clickHelpButtons()">' + el + '</button>'
        })

        // let paraminput = event.target.parentNode.querySelector('.param-input')
        // paraminput.value = ''
        // console.log (elemId, document.getElementById(elemId))
        document.getElementById(elemId).innerHTML = html
    }

    function clickHelpButtons() {
        let paramInput = event.target.parentNode.parentNode.querySelector('.param-input')

        if (event.target.classList.contains('active-help-button')) {
            // удаляем 
            event.target.classList.remove('active-help-button')
            if (paramInput.value.indexOf('/') < 0) paramInput.value = '';
            paramInput.value = paramInput.value.replace(event.target.innerHTML + '/', '')
            paramInput.value = paramInput.value.replace('/' + event.target.innerHTML, '')

        } else {
            // добавляем
            event.target.classList.add('active-help-button')
            if (paramInput.value == '') paramInput.value += event.target.innerHTML;
            else paramInput.value += '/' + event.target.innerHTML;
        }


    }

    function clickMasterButtons(elem) {

        mastersIDinput = document.getElementById('masters')
        mastersIDstring = mastersIDinput.value

        if (elem.classList.contains('active-help-button')) {
            // удаляем  мастера
            elem.classList.remove('active-help-button')
            // console.log (mastersIDstring)
            // mastersIDinput.value = mastersIDstring.value.replace('/'+elem.id. '')
            mastersIDinput.value = mastersIDstring.replace(elem.id + '/', '')
            mastersIDinput.value = mastersIDstring.replace('/' + elem.id, '')
            if (mastersIDstring.indexOf('/') < 0) mastersIDinput.value = '';
        } else {
            // добавляем мастера
            elem.classList.add('active-help-button')
            if (mastersIDstring == '') mastersIDinput.value += elem.id;
            else mastersIDinput.value += '/' + elem.id;
        }
    }

</script>