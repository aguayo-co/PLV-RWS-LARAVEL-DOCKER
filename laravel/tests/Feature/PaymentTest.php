<?php

namespace Tests\Feature;

use App\Brand;
use App\Campaign;
use App\Category;
use App\Color;
use App\Condition;
use App\Order;
use App\Product;
use App\Sale;
use App\ShippingMethod;
use App\Size;
use App\User;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class PaymentTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        factory(Brand::class)->create();
        factory(Campaign::class)->create();
        factory(Category::class)->create();
        factory(Category::class)->states(['subcategory'])->create();
        factory(Color::class, 2)->create();
        factory(Condition::class)->create();
        factory(ShippingMethod::class)->create();
        factory(Size::class)->create();
        factory(Size::class)->states(['child'])->create();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'seller']);

        $this->admin = factory(User::class)->create();
        $this->admin->assignRole('admin');
        $this->admin = $this->admin->fresh();
        $this->seller = factory(User::class)->states(['profile'])->create()->fresh();
        $this->user = factory(User::class)->create()->fresh();
    }

    protected function getProduct($status)
    {
        return factory(Product::class)->create(['status' => $status, 'user_id' => $this->seller->id]);
    }

    protected function getSale()
    {
        $product = $this->getProduct(Product::STATUS_APPROVED);
        $url = route('api.shopping_cart.update');
        $responseData = $this->actingAs($this->user)
            ->json('PATCH', $url, ['add_product_ids' => [$product->id]])->decodeResponseJson();
        $saleId = $responseData['sales'][0]['id'];

        return Sale::where('id', $saleId)->first();
    }

    public function testPaymentNeedsOrderInSHoppingCart()
    {
        $sale = $this->getSale();
        $sale->order->status = Order::STATUS_PAYMENT;
        $sale->order->save();

        $url = route('api.orders.payment.create', $sale->order);
        $response = $this->actingAs($this->user)->json('GET', $url);
        $response->assertStatus(422)
            ->assertJsonFragment(['Order is not in Shopping Cart.']);
    }

    public function testPaymentNeedsProducts()
    {
        $sale = $this->getSale();
        $sale->products[0]->status = Product::STATUS_SOLD;
        $sale->products[0]->save();

        $url = route('api.orders.payment.create', $sale->order);
        $response = $this->actingAs($this->user)->json('GET', $url);
        $response->assertStatus(424)
            ->assertJsonFragment(['No products in shopping cart.']);
    }

    public function testPaymentValidatesProducts()
    {
        $this->getSale();
        $sale = $this->getSale();
        $sale->products[0]->status = Product::STATUS_SOLD;
        $sale->products[0]->save();

        $url = route('api.orders.payment.create', $sale->order);
        $response = $this->actingAs($this->user)->json('GET', $url);
        $response->assertStatus(422)
            ->assertJsonFragment(['Some products are not available anymore.']);
    }

    public function testPaymentNeedsShippingMethods()
    {
        $sale = $this->getSale();

        $url = route('api.orders.payment.create', $sale->order);
        $response = $this->actingAs($this->user)->json('GET', $url);
        $response->assertStatus(422)
            ->assertJsonFragment(['Some sales do not have a ShippingMethod.']);
    }

    public function testPaymentNeedsShippingInfo()
    {
        $sale = $this->getSale();
        $sale->shipping_method_id = $sale->user->shipping_method_ids[0];
        $sale->save();

        $url = route('api.orders.payment.create', $sale->order);
        $response = $this->actingAs($this->user)->json('GET', $url);
        $response->assertStatus(422)
            ->assertJsonFragment(['Order needs shipping address.']);
    }
}
