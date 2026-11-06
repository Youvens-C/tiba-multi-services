<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** La racine exige une connexion : un visiteur est renvoyé vers /login. */
    public function test_la_racine_redirige_vers_la_connexion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
