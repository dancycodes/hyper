<div id="nested-container">
    @fragment('outer')
        <div id="outer-fragment">
            <h1>Outer Fragment</h1>
            @fragment('inner')
                <div id="inner-fragment">
                    <p>{{ $innerMessage ?? 'Inner content' }}</p>
                </div>
            @endfragment
        </div>
    @endfragment
</div>
