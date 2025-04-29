<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class PasswordMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['password'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        return password_hash($this->faker->password(), PASSWORD_BCRYPT);
    }
}
