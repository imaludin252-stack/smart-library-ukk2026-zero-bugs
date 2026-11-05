<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman akar mengarahkan pengunjung ke katalog publik.
     */
    public function test_the_application_redirects_to_the_public_catalog(): void
    {
        $this->get('/')->assertRedirect(route('catalog.index'));
    }
}
