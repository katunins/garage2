<link rel="stylesheet" href="css/welcome.css">
<link rel="stylesheet" href="css/general.css">
{{-- 0 => array:2 [▼
    "productname" => "Альбом из паспарту"
    "id" => "138"
  ] --}}
<h1>
    <a class="to-main-page" href="/"></a>
    Шаблоны задач</h1>
<div class="products-menu">
    @foreach ($products as $id=>$name)
    <li>
        <a href="/board/{{ $id }}">{{ $name }}</a>
    </li>
    @endforeach
</div>