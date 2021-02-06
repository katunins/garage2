<link rel="stylesheet" href="css/welcome.css">
<link rel="stylesheet" href="css/general.css">

<h1>
    <a class="to-main-page" href="/">←</a>
    Шаблоны задач</h1>
<div class="products-menu">
    @foreach ($products as $item)
    <li>
        <a href="/board/{{ $item->korobookid }}">{{ $item->title }}</a>
    </li>
    @endforeach
</div>