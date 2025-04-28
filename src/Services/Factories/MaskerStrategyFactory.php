<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Factories;

use Hristijans\DatabaseMasker\Contracts\ValueMaskerInterface;
use Hristijans\DatabaseMasker\Services\Maskers\AddressMasker;
use Hristijans\DatabaseMasker\Services\Maskers\CompanyMasker;
use Hristijans\DatabaseMasker\Services\Maskers\CreditCardMasker;
use Hristijans\DatabaseMasker\Services\Maskers\DateMasker;
use Hristijans\DatabaseMasker\Services\Maskers\DefaultMasker;
use Hristijans\DatabaseMasker\Services\Maskers\EmailMasker;
use Hristijans\DatabaseMasker\Services\Maskers\IpAddressMasker;
use Hristijans\DatabaseMasker\Services\Maskers\LocationMasker;
use Hristijans\DatabaseMasker\Services\Maskers\NameMasker;
use Hristijans\DatabaseMasker\Services\Maskers\NumberMasker;
use Hristijans\DatabaseMasker\Services\Maskers\PasswordMasker;
use Hristijans\DatabaseMasker\Services\Maskers\PatternMasker;
use Hristijans\DatabaseMasker\Services\Maskers\PhoneMasker;
use Hristijans\DatabaseMasker\Services\Maskers\TextMasker;
use Hristijans\DatabaseMasker\Services\Maskers\UuidMasker;

final class MaskerStrategyFactory
{
    /**
     * @var array<ValueMaskerInterface>
     */
    private array $maskers = [];

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->registerDefaultMaskers();
    }

    /**
     * Register all default maskers.
     */
    private function registerDefaultMaskers(): void
    {
        $this->maskers = [
            new EmailMasker,
            new NameMasker,
            new PhoneMasker,
            new AddressMasker,
            new LocationMasker,
            new TextMasker,
            new NumberMasker,
            new DateMasker,
            new PatternMasker,
            new CreditCardMasker,
            new CompanyMasker,
            new IpAddressMasker,
            new UuidMasker,
            new PasswordMasker,
        ];
    }

    /**
     * Create a masker for the given type.
     */
    public function createMasker(string $type): ValueMaskerInterface
    {
        foreach ($this->maskers as $masker) {
            if ($masker->canHandle($type)) {
                return $masker;
            }
        }

        // Return default masker if no specific masker found
        return new DefaultMasker;
    }

    /**
     * Register a custom masker.
     */
    public function registerMasker(ValueMaskerInterface $masker): void
    {
        // Add at the beginning so custom maskers take precedence
        array_unshift($this->maskers, $masker);
    }
}
