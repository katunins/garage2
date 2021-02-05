<link rel="stylesheet" href="css/welcome.css">

<div class="container">
    <h1>korobook Администратор</h1>
    <div class="container">
        <div class="menu">
            <li><a href="/templates">Шаблоны задач</a></li>
            <li><a href="/masters">Список мастеров</a></li>
            <li><a href="/tasksboard">Задачи</a></li>
            <li><a href="/calendar">Календарь</a></li>
        </div>
        <div class="">
            <form action="/deal2tasks" method="GET">
                {{-- @csrf --}}

                @error('deal')
                <p>{{ $message }}</p>
                @enderror
                <label for="id">ID сделки в Битрикс24</label>
                <input type="text" id="id" name="id">
                <p><input type="submit" name="" value="Создать"></p>
            </form>
        </div>
    </div>

</div>