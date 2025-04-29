<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Tests\Unit\Services\Factories;

use Hristijans\DatabaseMasker\Contracts\ValueMaskerInterface;
use Hristijans\DatabaseMasker\Services\Factories\MaskerStrategyFactory;
use Hristijans\DatabaseMasker\Services\Maskers\DefaultMasker;
use Hristijans\DatabaseMasker\Services\Maskers\EmailMasker;
use Hristijans\DatabaseMasker\Services\Maskers\NameMasker;
use Hristijans\DatabaseMasker\Tests\TestCase;

class MaskerStrategyFactoryTest extends TestCase
{
    private MaskerStrategyFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new MaskerStrategyFactory;
    }

    /**
     * Test creating maskers for different types.
     */
    public function test_create_masker(): void
    {
        // Test creating an email masker
        $emailMasker = $this->factory->createMasker('email');
        $this->assertInstanceOf(ValueMaskerInterface::class, $emailMasker);
        $this->assertInstanceOf(EmailMasker::class, $emailMasker);

        // Test creating a name masker
        $nameMasker = $this->factory->createMasker('name');
        $this->assertInstanceOf(ValueMaskerInterface::class, $nameMasker);
        $this->assertInstanceOf(NameMasker::class, $nameMasker);

        // Test creating an undefined masker type (should return default masker)
        $unknownMasker = $this->factory->createMasker('nonexistent_type');
        $this->assertInstanceOf(ValueMaskerInterface::class, $unknownMasker);
        $this->assertInstanceOf(DefaultMasker::class, $unknownMasker);
    }

    /**
     * Test registering a custom masker.
     */
    public function test_register_custom_masker(): void
    {
        // Create a mock custom masker
        $customMasker = new class implements ValueMaskerInterface
        {
            public function canHandle(string $type): bool
            {
                return $type === 'custom_type';
            }

            public function mask(mixed $originalValue, array $columnConfig): mixed
            {
                return 'custom_masked_value';
            }
        };

        // Register the custom masker
        $this->factory->registerMasker($customMasker);

        // Test that the factory now returns our custom masker for the custom type
        $masker = $this->factory->createMasker('custom_type');
        $this->assertSame($customMasker, $masker);

        // Test that masking with our custom masker works as expected
        $result = $masker->mask('original value', ['type' => 'custom_type']);
        $this->assertEquals('custom_masked_value', $result);
    }
}
