<link rel="stylesheet" href="css/welcome.css">

<h1>Шаблоны задач</h1>
<div class="products-menu">
    @foreach ($products as $item)
    <li>
        <a href="/board/{{ $item->korobookid }}">{{ $item->title }}</a>
    </li>
    @endforeach
</div>