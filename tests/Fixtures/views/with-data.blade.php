<div id="with-data-view">
    <h1>{{ $title }}</h1>
    <p>{{ $description }}</p>
    @if(isset($items))
        <ul>
            @foreach($items as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif
</div>
