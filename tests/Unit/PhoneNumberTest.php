<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public static function validNumbers(): array
    {
        return [
            'local mobile with punctuation' => ['0812-3456-7890', '6281234567890'],
            'international with plus and spaces' => ['+62 812 3456 7890', '6281234567890'],
            'country code without plus' => ['62 812 3456 7890', '6281234567890'],
            'mobile without leading zero' => ['812 3456 7890', '6281234567890'],
            'dots and parentheses' => ['(0812) 3456.7890', '6281234567890'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_it_normalizes_indonesian_whatsapp_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    public function test_it_rejects_a_number_that_is_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PhoneNumber::normalize('0812');
    }

    public function test_it_rejects_a_number_with_letters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PhoneNumber::normalize('0812-ABCD-7890');
    }
}
