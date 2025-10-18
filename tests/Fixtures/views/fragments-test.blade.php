<div>
    @fragment('header')
        <h1>{{ $title ?? 'Default Title' }}</h1>
    @endfragment

    @fragment('content')
        <p>{{ $message ?? 'Default message' }}</p>
    @endfragment

    @fragment('footer')
        <footer>Copyright {{ $year ?? date('Y') }}</footer>
    @endfragment

    @fragment('nested-outer')
        <div class="outer">
            @fragment('nested-inner')
                <div class="inner">Inner content</div>
            @endfragment
        </div>
    @endfragment

    @fragment('with-blade')
        @if($show ?? true)
            <div>Conditional content</div>
        @endif
        @foreach($items ?? [] as $item)
            <li>{{ $item }}</li>
        @endforeach
    @endfragment
</div>
