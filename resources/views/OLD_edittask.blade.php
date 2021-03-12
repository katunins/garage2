<link rel="stylesheet" href="/css/newtemplate.css">
<link rel="stylesheet" href="/css/general.css">

<h1>
    <a class="to-main-page" href="/"></a>
    Правка задачи
</h1>
<p>{{ $Task->name }} - {{ $Task->deal }}</p>
<form action="/saveedittask" method="POST">
    @csrf
    <input type="hidden" name="taskid" value="{{ $Task->id }}">

    <div class="input-blocks">
        <div class="input-group">
            <div>
                <label for="master">ID мастера</label>
                @error ('master')
                    <p class="alert">{{ $message }}</p>
                @enderror
                <input name="master" list="masters" value="{{ $Task->master }}">
                <button type="button" onclick="this.parentNode.querySelector('input').value=''">×</button>
            </div>

            <datalist id="masters">
                @foreach ($Users as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </datalist>

            <div>
                @error ('taskname')
                    <p class="alert">{{ $message }}</p>
                @enderror
                <label for="taskname">Название задачи</label>
                <input class="max-size" type="text" id="taskname" name="taskname" value="{{ $Task->name }}"
                    placeholder="Подготовка картона">
            </div>

            <div>
                <label for="generalinfo">Основная информация</label>
                @error ('generalinfo')
                    <p class="alert">{{ $message }}</p>
                @enderror
                <input class="max-size" name="generalinfo" value="{{ $Task->generalinfo }}">
            </div>

            <div>
                <label for="info">Дополнительная информация</label>
                @error ('info')
                    <p class="alert">{{ $message }}</p>
                @enderror
                <input class="max-size" name="info" value="{{ $Task->info }}">
            </div>

            <div class="param-input-help">

            </div>
            <div class="param-input-help">
                <div>
                    @error ('start')
                        <p class="alert">{{ $message }}</p>
                    @enderror
                    <label for="start">Время начала</label>
                    <input class="middle-size" type="text" id="start" name="start" value="{{ $Task->start }}">
                </div>
                <div>
                    <label for="master">Время, мин</label>
                    @error ('time')
                        <p class="alert">{{ $message }}</p>
                    @enderror
                    <input name="time" value="{{ $Task->time }}">
                </div>
            </div>

        </div>

        {{-- <div class="input-group">
            <div class="param-input-help">
                <div>
                    <label for="master">Время, мин</label>
@error ('time')
                        <p class="alert">{{ $message }}</p>
        @enderror
        <input name="time" value="{{ $Task->time }}">
    </div>
    <div>
        <label for="buffer">Буфер, мин</label>
        @error ('time')
            <p class="alert">{{ $message }}</p>
        @enderror
        <input name="buffer" value="{{ $Task->buffer }}">
    </div>
    <div>
        <label for="master">ID мастера</label>
        @error ('master')
            <p class="alert">{{ $message }}</p>
        @enderror
        <input name="master" list="masters" value="{{ $Task->master }}">
        <button type="button" onclick="this.parentNode.querySelector('input').value=''">×</button>
    </div>

    <datalist id="masters">
        @foreach ($Users as $item)
            <option value="{{ $item->id }}">{{ $item->name }}</option>
        @endforeach
    </datalist>
    </div>
    <div>
        @error ('start')
            <p class="alert">{{ $message }}</p>
        @enderror
        <label for="start">Время начала</label>
        <input class="middle-size" type="text" id="start" name="start" value="{{ $Task->start }}">
    </div>

    <div>
        @error ('end')
            <p class="alert">{{ $message }}</p>
        @enderror
        <label for="end">Время окончания</label>
        <input class="middle-size" type="text" id="end" name="end" value="{{ $Task->end }}">
    </div>

    </div> --}}

    </div>

    <div class="buttons">
        <a href="{{ url()->previous() }}">Назад</a>
        <input type="submit" value="Сохранить">
    </div>

</form>
