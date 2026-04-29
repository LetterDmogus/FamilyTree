<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        
        // Disable CSRF for all tests globally
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
{
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
