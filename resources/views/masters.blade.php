<link rel="stylesheet" href="css/welcome.css">
<link rel="stylesheet" href="css/general.css">

<div class="container">
    <h1>
        <a class="to-main-page" href="/">←</a>
        Мастера</h1>
    <table>
        <tr>
            <th>id</th>
            <th>Имя</th>
            <th>Действия</th>
        </tr>
        @foreach ($masters as $item)
        <tr>
            <td>{{ $item->id }}</td>
            <td><a href="/master/{{ $item->id }}">{{ $item->name }}</a></td>
            <td>

                <form action="/masteredit" method="GET">
                    <input type="hidden" name="type" value="newpass">
                    <input type="text" name="password" id="password" placeholder="Новый пароль">
                    <input type="hidden" name="id" value={{ $item->id }}>
                    <input type="submit" value="Изменить пароль">
                </form>
                <form action="/masteredit" method="GET">
                    <input type="hidden" name="type" value="delete">
                    <input type="hidden" name="id" value={{ $item->id }}>
                    <input type="submit" value="Удалить">
                </form>
            </td>
        </tr>
        @endforeach

    </table>
    <form action="/masteredit" method="GET">
        <p>Добавить мастера</p>
        <div class="">
            <input type="text" name="name" id="name">
            <label for="name">Имя</label>
        </div>
        <div class="">
            <input type="number" name="bitrixid" id="bitrixid">
            <label for="bitrixid">ID в Битрикс24</label>
        </div>
        <div class="">
            <input type="text" name="password" id="password">
            <label for="pasword">Пароль</label>
        </div>
        <input type="hidden" name="type" value="new">
        <input type="submit" value="Сохранить">
    </form>
</div>