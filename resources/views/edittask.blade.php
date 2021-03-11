<link rel="stylesheet" href="/css/newtemplate.css">
<link rel="stylesheet" href="/css/general.css">

<h1>
    <a class="to-main-page" href="/"></a>
    Правка задачи
</h1>
<p>{{ $Task->name }}</p>
<form action="/saveedittask" method="POST">
    @csrf
    <input type="hidden" name="taskid" value="{{ $Task->id }}">
    <div class="buttons">
        <a href="{{ url()->previous() }}">Назад</a>
        <input type="submit" value="Сохранить">
    </div>

    <div class="input-blocks">
        <div class="input-group">
            <div>
                @error ('taskname')
                    <p class="alert">{{ $message }}</p>
                @enderror
                <label for="taskname">Название задачи</label>
                <input class="max-size" type="text" id="taskname" name="taskname" value="{{ $Task->name }}"
                    placeholder="Подготовка картона">
            </div>
            <div class="param-input-help">
                <label> Мини-параметры</label>
                @for ($i = 0; $i < 3; $i++)
                    <div>
                        <input name="miniparams[{{ $i }}]" list="conditions" placeholder="Выберете параметр" value="">
                        <button type="button" onclick="this.parentNode.querySelector('input').value=''">×</button>
                    </div>
                @endfor
            </div>

        </div>


    </div>

</form>
