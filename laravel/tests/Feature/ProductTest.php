<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductTest extends TestCase
{
    public function testCreateProductIsProtected()
    {
        $middleware = app()->routes->getByName('api.product.create')->gatherMiddleware();
        $this->assertContains('role:seller|admin', $middleware);
    }
}
