<div id="fragments-container">
    <h1>View with Fragments</h1>

    @fragment('header')
        <header id="header-fragment">
            <h2>{{ $title ?? 'Default Title' }}</h2>
        </header>
    @endfragment

    @fragment('content')
        <main id="content-fragment">
            <p>{{ $message ?? 'Default message' }}</p>
            @if(isset($items))
                <ul>
                    @foreach($items as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </main>
    @endfragment

    @fragment('footer')
        <footer id="footer-fragment">
            <p>{{ $copyright ?? '© 2025' }}</p>
        </footer>
    @endfragment
</div>
