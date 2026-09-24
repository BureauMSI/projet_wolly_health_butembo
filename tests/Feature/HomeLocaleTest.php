<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeLocaleTest extends TestCase
{
    public function test_home_page_uses_french_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Holy Health')
            ->assertSee('Gestion du marketing de réseau')
            ->assertSee('navbar', false);
    }

    public function test_locale_switch_loads_swahili_strings(): void
    {
        $this->from('/')
            ->get('/locale/sw')
            ->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('Usimamizi wa uuzaji wa mtandao')
            ->assertDontSee('Gestion du marketing de réseau');
    }
}
