<?php

namespace Dancycodes\Hyper\Tests\Feature;

use Dancycodes\Hyper\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Test Complete Real-World Workflows
 *
 * @see TESTING.md - File 56: CompleteWorkflow Tests
 * Status: 🔄 IN PROGRESS - 15 test methods
 */
class CompleteWorkflowTest extends TestCase
{
    public static $latestResponse;

    protected string $validPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function test_todo_list_complete_workflow()
    {
        // Complete CRUD with signals, fragments, validation
        Route::post('/todos', function () {
            signals()->validate(['title' => 'required|min:3']);

            return hyper()
                ->signals(['todos' => [['id' => 1, 'title' => signals('title'), 'done' => false]]])
                ->signals(['title' => '']); // Clear input
        });
        $signals = json_encode(['title' => 'Buy groceries']);
        $response = $this->call('POST', '/todos', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_form_with_file_upload_workflow()
    {
        Route::post('/profile', function () {
            signals()->validate([
                'name' => 'required',
                'avatar' => 'required|b64image|b64max:1024',
            ]);
            $path = hyperStorage()->store('avatar', 'avatars', 'public');

            return hyper()->signals([
                'saved' => true,
                'avatarUrl' => $path,
            ]);
        });
        $signals = json_encode([
            'name' => 'John Doe',
            'avatar' => $this->validPngBase64,
        ]);
        $response = $this->call('POST', '/profile', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_multi_step_wizard_workflow()
    {
        Route::post('/wizard/step/{step}', function ($step) {
            $currentStep = (int) $step;
            if ($currentStep === 1) {
                signals()->validate(['email' => 'required|email']);
            }
            if ($currentStep === 2) {
                signals()->validate(['password' => 'required|min:8']);
            }

            return hyper()->signals([
                'currentStep' => $currentStep + 1,
                'completed' => $currentStep >= 3,
            ]);
        });
        // Step 1
        $response1 = $this->call('POST', '/wizard/step/1', [
            'datastar' => json_encode(['email' => 'test@example.com']),
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response1->assertOk();
    }

    /** @test */
    public function test_live_search_workflow()
    {
        Route::get('/search', function () {
            $query = signals('search', '');
            $results = !empty($query) ? ['Item 1', 'Item 2'] : [];

            return hyper()->signals([
                'results' => $results,
                'searching' => false,
            ]);
        });
        $response = $this->call('GET', '/search', [
            'datastar' => json_encode(['search' => 'test']),
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_pagination_workflow()
    {
        Route::get('/items', function () {
            $page = signals('page', 1);
            $items = array_slice(range(1, 100), ($page - 1) * 10, 10);

            return hyper()->signals([
                'items' => $items,
                'page' => $page,
                'hasMore' => $page < 10,
            ]);
        });
        $response = $this->call('GET', '/items', [
            'datastar' => json_encode(['page' => 2]),
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_modal_workflow()
    {
        Route::post('/modal/open', function () {
            return hyper()->signals([
                'modalOpen' => true,
                'modalContent' => 'Modal content here',
            ]);
        });
        Route::post('/modal/close', function () {
            return hyper()->signals([
                'modalOpen' => false,
                'modalContent' => '',
            ]);
        });
        $response = $this->call('POST', '/modal/open', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_notification_workflow()
    {
        Route::post('/notify', function () {
            return hyper()->signals([
                'notifications' => [
                    ['id' => 1, 'message' => 'Success!', 'type' => 'success'],
                ],
            ]);
        });
        $response = $this->call('POST', '/notify', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_chat_application_workflow()
    {
        Route::post('/chat/send', function () {
            $message = signals('message', '');
            $messages = signals('messages', []);
            $messages[] = ['user' => 'Me', 'text' => $message];

            return hyper()->signals([
                'messages' => $messages,
                'message' => '', // Clear input
            ]);
        });
        $signals = json_encode([
            'message' => 'Hello world',
            'messages' => [],
        ]);
        $response = $this->call('POST', '/chat/send', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_dashboard_real_time_updates()
    {
        Route::get('/dashboard/stats', function () {
            return hyper()->signals([
                'users' => 150,
                'revenue' => 45000,
                'orders' => 89,
                'lastUpdate' => now()->toIso8601String(),
            ]);
        });
        $response = $this->call('GET', '/dashboard/stats', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_shopping_cart_workflow()
    {
        Route::post('/cart/add', function () {
            $cart = signals('cart', []);
            $productId = signals('productId');
            $cart[] = ['id' => $productId, 'qty' => 1];

            return hyper()->signals([
                'cart' => $cart,
                'cartCount' => count($cart),
            ]);
        });
        $signals = json_encode([
            'cart' => [],
            'productId' => 123,
        ]);
        $response = $this->call('POST', '/cart/add', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_authentication_workflow()
    {
        Route::post('/auth/login', function () {
            signals()->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            return hyper()->signals([
                'authenticated' => true,
                'user' => ['name' => 'John Doe', 'email' => signals('email')],
            ]);
        });
        $signals = json_encode([
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);
        $response = $this->call('POST', '/auth/login', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_infinite_scroll_workflow()
    {
        Route::get('/posts', function () {
            $page = signals('page', 1);
            $posts = signals('posts', []);
            $newPosts = [
                ['id' => $page * 10, 'title' => "Post {$page}"],
            ];
            $posts = array_merge($posts, $newPosts);

            return hyper()->signals([
                'posts' => $posts,
                'page' => $page + 1,
                'hasMore' => $page < 5,
            ]);
        });
        $signals = json_encode(['page' => 1, 'posts' => []]);
        $response = $this->call('GET', '/posts', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_drag_and_drop_workflow()
    {
        Route::post('/items/reorder', function () {
            $items = signals('items', []);
            $from = signals('from');
            $to = signals('to');
            // Simple reorder logic
            $item = $items[$from];
            unset($items[$from]);
            array_splice($items, $to, 0, [$item]);

            return hyper()->signals(['items' => array_values($items)]);
        });
        $signals = json_encode([
            'items' => ['A', 'B', 'C', 'D'],
            'from' => 0,
            'to' => 2,
        ]);
        $response = $this->call('POST', '/items/reorder', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }

    /** @test */
    public function test_nested_components_workflow()
    {
        Route::post('/parent/update', function () {
            return hyper()->signals([
                'parent' => [
                    'name' => 'Parent Component',
                    'children' => [
                        ['id' => 1, 'name' => 'Child 1'],
                        ['id' => 2, 'name' => 'Child 2'],
                    ],
                ],
            ]);
        });
        $response = $this->call('POST', '/parent/update', [], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
        $response->assertOk();
    }

    /** @test */
    public function test_complex_form_with_conditional_fields()
    {
        Route::post('/form/submit', function () {
            $accountType = signals('accountType');
            $rules = [
                'name' => 'required',
                'accountType' => 'required|in:personal,business',
            ];
            if ($accountType === 'business') {
                $rules['company'] = 'required';
                $rules['taxId'] = 'required';
            }
            signals()->validate($rules);

            return hyper()->signals([
                'submitted' => true,
                'accountType' => $accountType,
            ]);
        });
        $signals = json_encode([
            'name' => 'John Doe',
            'accountType' => 'business',
            'company' => 'Acme Corp',
            'taxId' => '123456789',
        ]);
        $response = $this->call('POST', '/form/submit', [
            'datastar' => $signals,
        ], [], [], ['HTTP_DATASTAR_REQUEST' => 'true']);
        $response->assertOk();
    }
}
