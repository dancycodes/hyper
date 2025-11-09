<?php

use Dancycodes\Hyper\Html\Html;

/**
 * Action Methods Tests
 *
 * Tests for HasActionMethods trait that provides simplified action methods
 * with smart event detection for Datastar HTTP actions.
 *
 * Note: HTML attributes are encoded for XSS protection, so we check for
 * key components rather than exact string matches.
 */
describe('Action Methods', function () {
    describe('CSRF-Protected Actions (Hyper Extensions)', function () {
        it('generates postx action with smart event detection for button', function () {
            $html = Html::button()->postx('/save')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@postx')
                ->toContain('/save');
        });

        it('generates postx action with smart event detection for form', function () {
            $html = Html::form()->postx('/submit')->render();

            expect($html)->toContain('data-on:submit__prevent')
                ->toContain('@postx')
                ->toContain('/submit');
        });

        it('generates putx action with default event', function () {
            $html = Html::button()->putx('/update')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@putx')
                ->toContain('/update');
        });

        it('generates patchx action with default event', function () {
            $html = Html::button()->patchx('/patch')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@patchx')
                ->toContain('/patch');
        });

        it('generates deletex action with default event', function () {
            $html = Html::button()->deletex('/delete')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@deletex')
                ->toContain('/delete');
        });

        it('allows explicit event override for postx', function () {
            $html = Html::div()->postx('/save', 'dblclick')->render();

            expect($html)->toContain('data-on:dblclick')
                ->toContain('@postx')
                ->toContain('/save');
        });

        it('supports event modifiers with explicit event for postx', function () {
            $html = Html::div()->postx('/save', 'click__prevent__stop')->render();

            expect($html)->toContain('data-on:click__prevent__stop')
                ->toContain('@postx')
                ->toContain('/save');
        });
    });

    describe('Standard Datastar Actions', function () {
        it('generates get action with smart event detection for button', function () {
            $html = Html::button()->get('/fetch')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@get')
                ->toContain('/fetch');
        });

        it('generates get action with smart event detection for input', function () {
            $html = Html::input()->get('/search')->render();

            expect($html)->toContain('data-on:input')
                ->toContain('@get')
                ->toContain('/search');
        });

        it('generates post action without CSRF', function () {
            $html = Html::button()->post('/api/data')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@post')
                ->toContain('/api/data');
        });

        it('generates put action without CSRF', function () {
            $html = Html::button()->put('/api/update')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@put')
                ->toContain('/api/update');
        });

        it('generates patch action without CSRF', function () {
            $html = Html::button()->patch('/api/patch')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@patch')
                ->toContain('/api/patch');
        });

        it('generates delete action without CSRF', function () {
            $html = Html::button()->delete('/api/delete')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@delete')
                ->toContain('/api/delete');
        });
    });

    describe('Smart Event Detection', function () {
        it('uses submit__prevent for form elements', function () {
            $html = Html::form()->postx('/submit')->render();

            expect($html)->toContain('data-on:submit__prevent');
        });

        it('uses click for button elements', function () {
            $html = Html::button()->postx('/save')->render();

            expect($html)->toContain('data-on:click');
        });

        it('uses click for anchor elements', function () {
            $html = Html::a()->get('/fetch')->render();

            expect($html)->toContain('data-on:click');
        });

        it('uses input for text input elements', function () {
            $html = Html::input()->type('text')->get('/search')->render();

            expect($html)->toContain('data-on:input');
        });

        it('uses input for email input elements', function () {
            $html = Html::input()->type('email')->get('/validate')->render();

            expect($html)->toContain('data-on:input');
        });

        it('uses input for number input elements', function () {
            $html = Html::input()->type('number')->get('/calculate')->render();

            expect($html)->toContain('data-on:input');
        });

        it('uses input for search input elements', function () {
            $html = Html::input()->type('search')->get('/search')->render();

            expect($html)->toContain('data-on:input');
        });

        it('uses change for checkbox input elements', function () {
            $html = Html::input()->type('checkbox')->patchx('/toggle')->render();

            expect($html)->toContain('data-on:change')
                ->toContain('@patchx');
        });

        it('uses change for radio input elements', function () {
            $html = Html::input()->type('radio')->patchx('/select')->render();

            expect($html)->toContain('data-on:change')
                ->toContain('@patchx');
        });

        it('uses change for file input elements', function () {
            $html = Html::input()->type('file')->postx('/upload')->render();

            expect($html)->toContain('data-on:change')
                ->toContain('@postx');
        });

        it('uses change for color input elements', function () {
            $html = Html::input()->type('color')->patchx('/update-color')->render();

            expect($html)->toContain('data-on:change')
                ->toContain('@patchx');
        });

        it('uses change for select elements', function () {
            $html = Html::select()->get('/filter')->render();

            expect($html)->toContain('data-on:change');
        });

        it('uses click as default for div elements', function () {
            $html = Html::div()->postx('/update')->render();

            expect($html)->toContain('data-on:click');
        });

        it('uses click as default for other elements', function () {
            $html = Html::div()->get('/load')->render();

            expect($html)->toContain('data-on:click');
        });

        it('allows explicit event to override smart default for form', function () {
            $html = Html::form()->postx('/submit', 'change')->render();

            expect($html)->toContain('data-on:change')
                ->toContain('@postx')
                ->toContain('/submit');
        });

        it('allows explicit event to override smart default for checkbox', function () {
            $html = Html::input()->type('checkbox')->patchx('/toggle', 'click')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@patchx');
        });
    });

    describe('Options Serialization', function () {
        it('serializes options with XSS protection', function () {
            $html = Html::button()->postx('/save', null, [
                'headers' => ['X-Custom' => 'value'],
                'retry' => 3,
            ])->render();

            expect($html)
                ->toContain('data-on:click')
                ->toContain('@postx')
                ->toContain('/save')
                ->toContain('headers')
                ->toContain('X-Custom')
                ->toContain('retry');
        });

        it('handles complex options correctly', function () {
            $html = Html::button()->get('/fetch', null, [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Custom-Header' => 'test',
                ],
                'retry' => 5,
                'credentials' => 'include',
            ])->render();

            expect($html)
                ->toContain('headers')
                ->toContain('Accept')
                ->toContain('retry')
                ->toContain('credentials');
        });

        it('omits options object when options array is empty', function () {
            $html = Html::button()->postx('/save', null, [])->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@postx')
                ->toContain('/save')
                ->not->toContain('headers')
                ->not->toContain('retry');
        });

        it('includes options when explicitly provided with explicit event', function () {
            $html = Html::div()->postx('/save', 'dblclick', ['retry' => 2])->render();

            expect($html)
                ->toContain('data-on:dblclick')
                ->toContain('@postx')
                ->toContain('/save')
                ->toContain('retry');
        });
    });

    describe('Navigate Action', function () {
        it('generates navigate action with default event', function () {
            $html = Html::a()->navigate('/dashboard')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@navigate')
                ->toContain('/dashboard');
        });

        it('generates navigate action with key parameter', function () {
            $html = Html::a()->navigate('/sidebar', null, 'sidebar-content')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@navigate')
                ->toContain('/sidebar')
                ->toContain('sidebar-content');
        });

        it('generates navigate action with key and options', function () {
            $html = Html::a()->navigate('/sidebar', null, 'sidebar', ['headers' => ['X-Partial' => 'true']])->render();

            expect($html)
                ->toContain('data-on:click')
                ->toContain('@navigate')
                ->toContain('/sidebar')
                ->toContain('sidebar')
                ->toContain('headers')
                ->toContain('X-Partial');
        });

        it('generates navigate action with explicit event', function () {
            $html = Html::div()->navigate('/page', 'dblclick')->render();

            expect($html)->toContain('data-on:dblclick')
                ->toContain('@navigate')
                ->toContain('/page');
        });

        it('generates navigate action with explicit event and key', function () {
            $html = Html::div()->navigate('/content', 'click__window', 'main')->render();

            expect($html)->toContain('data-on:click__window')
                ->toContain('@navigate')
                ->toContain('/content')
                ->toContain('main');
        });
    });

    describe('Dispatch Action', function () {
        it('generates dispatch action without detail', function () {
            $html = Html::button()->dispatch('modal-open')->render();

            expect($html)->toContain('data-on:click')
                ->toContain('@dispatch')
                ->toContain('modal-open');
        });

        it('generates dispatch action with detail data', function () {
            $html = Html::button()->dispatch('user-selected', null, ['userId' => 123])->render();

            expect($html)
                ->toContain('data-on:click')
                ->toContain('@dispatch')
                ->toContain('user-selected')
                ->toContain('userId');
        });

        it('generates dispatch action with explicit event', function () {
            $html = Html::div()->dispatch('item-clicked', 'dblclick')->render();

            expect($html)->toContain('data-on:dblclick')
                ->toContain('@dispatch')
                ->toContain('item-clicked');
        });

        it('serializes detail with XSS protection', function () {
            $html = Html::button()->dispatch('data-ready', null, [
                'message' => 'Test <script>alert("xss")</script>',
                'count' => 42,
            ])->render();

            expect($html)
                ->toContain('@dispatch')
                ->toContain('data-ready')
                ->toContain('message')
                ->toContain('count')
                ->not->toContain('<script>'); // XSS protected
        });
    });

    describe('Method Chaining', function () {
        it('allows chaining action methods with other methods', function () {
            $html = Html::button()
                ->class('btn btn-primary')
                ->id('save-btn')
                ->postx('/save')
                ->dataIndicator('saving')
                ->text('Save')
                ->render();

            expect($html)
                ->toContain('class="btn btn-primary"')
                ->toContain('id="save-btn"')
                ->toContain('data-on:click')
                ->toContain('@postx')
                ->toContain('data-indicator="saving"')
                ->toContain('>Save<');
        });

        it('allows multiple action methods on same element', function () {
            $html = Html::input()
                ->type('text')
                ->get('/autosave', 'input__debounce.500ms')
                ->get('/validate', 'blur')
                ->render();

            expect($html)
                ->toContain('data-on:input__debounce.500ms')
                ->toContain('data-on:blur')
                ->toContain('/autosave')
                ->toContain('/validate');
        });
    });

    describe('Void Elements Support', function () {
        it('works with void elements like input', function () {
            $html = Html::input()
                ->type('text')
                ->name('search')
                ->get('/search')
                ->render();

            expect($html)->toContain('data-on:input')
                ->toContain('@get')
                ->toContain('/search')
                ->toContain('/>'); // Void element self-closing
        });

        it('works with void elements like img with explicit event', function () {
            $html = Html::img()
                ->src('/placeholder.jpg')
                ->get('/lazy-load', 'load')
                ->render();

            expect($html)->toContain('data-on:load')
                ->toContain('@get')
                ->toContain('/lazy-load');
        });
    });
});

describe('Loading States (dataIndicator)', function () {
    it('generates data-indicator attribute', function () {
        $html = Html::button()->dataIndicator('saving')->render();

        expect($html)->toContain('data-indicator="saving"');
    });

    it('can be combined with action methods', function () {
        $html = Html::button()
            ->dataIndicator('loading')
            ->get('/fetch')
            ->render();

        expect($html)
            ->toContain('data-indicator="loading"')
            ->toContain('data-on:click')
            ->toContain('@get');
    });

    it('can be combined with conditional attributes', function () {
        $html = Html::button()
            ->dataIndicator('submitting')
            ->postx('/submit')
            ->dataAttr('disabled', '$submitting')
            ->render();

        expect($html)
            ->toContain('data-indicator="submitting"')
            ->toContain('data-on:click')
            ->toContain('@postx')
            ->toContain('data-attr:disabled');
    });

    it('works with form elements', function () {
        $html = Html::form()
            ->dataIndicator('processing')
            ->postx('/form-submit')
            ->render();

        expect($html)
            ->toContain('data-indicator="processing"')
            ->toContain('data-on:submit__prevent')
            ->toContain('@postx');
    });
});

describe('Real-World Patterns', function () {
    it('creates complete search input with debounced action', function () {
        $html = Html::input()
            ->type('search')
            ->name('q')
            ->placeholder('Search...')
            ->dataBind('searchQuery')
            ->get('/search', 'input__debounce.300ms')
            ->dataIndicator('searching')
            ->render();

        expect($html)
            ->toContain('type="search"')
            ->toContain('data-bind="searchQuery"')
            ->toContain('data-on:input__debounce.300ms')
            ->toContain('@get')
            ->toContain('data-indicator="searching"');
    });

    it('creates save button with loading state', function () {
        $html = Html::button()
            ->type('submit')
            ->class('btn btn-primary')
            ->dataIndicator('saving')
            ->postx('/save')
            ->dataAttr('disabled', '$saving')
            ->content(
                Html::span()->dataShow('!$saving')->text('Save'),
                Html::span()->dataShow('$saving')->text('Saving...')
            )
            ->render();

        expect($html)
            ->toContain('data-indicator="saving"')
            ->toContain('data-on:click')
            ->toContain('@postx')
            ->toContain('data-attr:disabled')
            ->toContain('data-show=');
    });

    it('creates delete button with confirmation', function () {
        $html = Html::button()
            ->class('btn btn-danger')
            ->dataIndicator('deleting')
            ->deletex('/delete/123', 'click__window')
            ->dataAttr('disabled', '$deleting')
            ->text('Delete')
            ->render();

        expect($html)
            ->toContain('data-on:click__window')
            ->toContain('@deletex')
            ->toContain('/delete/123')
            ->toContain('data-indicator="deleting"')
            ->toContain('data-attr:disabled');
    });

    it('creates navigation link with custom options', function () {
        $html = Html::a()
            ->href('/dashboard')
            ->class('nav-link')
            ->navigate('/dashboard', null, 'main-content', [
                'headers' => ['X-Partial' => 'true'],
            ])
            ->text('Dashboard')
            ->render();

        expect($html)
            ->toContain('href="/dashboard"')
            ->toContain('data-on:click')
            ->toContain('@navigate')
            ->toContain('main-content')
            ->toContain('X-Partial');
    });

    it('creates form with multiple submit handlers', function () {
        $html = Html::form()
            ->dataSignals(['status' => 'draft'])
            ->dataIndicator('submitting')
            ->content(
                Html::input()->type('text')->name('title')->dataBind('title'),
                Html::button()
                    ->type('submit')
                    ->postx('/save?status=draft')
                    ->dataAttr('disabled', '$submitting')
                    ->text('Save Draft'),
                Html::button()
                    ->type('submit')
                    ->postx('/save?status=published')
                    ->dataAttr('disabled', '$submitting')
                    ->text('Publish')
            )
            ->render();

        expect($html)
            ->toContain('data-signals=')
            ->toContain('data-indicator="submitting"')
            ->toContain('status=draft')
            ->toContain('status=published');
    });
});
