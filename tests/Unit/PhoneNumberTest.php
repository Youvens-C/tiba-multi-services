<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Règles de validation des numéros (remarques 5 et 6) :
 * 8 chiffres = Moncash/Natcash, 10 chiffres = Zelle, aucun numéro négatif.
 */
class PhoneNumberTest extends TestCase
{
    public static function validNumbers(): array
    {
        return [
            'haïti simple'            => ['37123456', 'moncash', '37123456'],
            'haïti avec +509'         => ['+509 3712-3456', 'moncash', '37123456'],
            'haïti 509 sans plus'     => ['509 37123456', 'natcash', '37123456'],
            'haïti parenthèses'       => ['(509) 3712 3456', 'natcash', '37123456'],
            'zelle simple'            => ['3055551234', 'zelle', '3055551234'],
            'zelle +1 parenthèses'    => ['+1 (305) 555-1234', 'zelle', '3055551234'],
            'zelle 1 et tirets'       => ['1-305-555-1234', 'zelle', '3055551234'],
            'zelle 11 chiffres'       => ['13055551234', 'zelle', '3055551234'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_numero_valide(string $raw, string $service, string $core): void
    {
        $r = PhoneNumber::forService($raw, $service);
        $this->assertTrue($r['ok'], (string) $r['error']);
        $this->assertSame($core, $r['core']);
    }

    public static function invalidNumbers(): array
    {
        return [
            'négatif 10 chiffres'     => ['-1234567890', 'zelle'],
            'négatif 8 chiffres'      => ['-33567890', 'moncash'],
            'commence par un point'   => ['.37123456', 'moncash'],
            'lettres'                 => ['abc37123456', 'moncash'],
            'lettre au milieu'        => ['3712a456', 'moncash'],
            'plus à la fin'           => ['37123456+', 'moncash'],
            'trop court'              => ['3712345', 'moncash'],
            'trop long (9)'           => ['371234567', 'moncash'],
            'indicatif régional 1'    => ['1234567890', 'zelle'],
            'indicatif régional 0'    => ['0305551234', 'zelle'],
            '10 chiffres sur Moncash' => ['3055551234', 'moncash'],
            '8 chiffres sur Zelle'    => ['37123456', 'zelle'],
            'vide'                    => ['', 'moncash'],
            'espaces'                 => ['   ', 'zelle'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_numero_refuse(string $raw, string $service): void
    {
        $this->assertFalse(PhoneNumber::forService($raw, $service)['ok']);
    }

    public function test_dix_chiffres_sont_un_numero_zelle(): void
    {
        $this->assertSame(PhoneNumber::KIND_US, PhoneNumber::analyse('3055551234')['kind']);
        $this->assertSame(PhoneNumber::KIND_HAITI, PhoneNumber::analyse('37123456')['kind']);
    }
}
