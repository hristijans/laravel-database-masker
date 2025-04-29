<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Tests\Unit\Services\Maskers;

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
use Hristijans\DatabaseMasker\Tests\TestCase;

class ValueMaskerTest extends TestCase
{
    /**
     * Test email masker.
     */
    public function test_email_masker(): void
    {
        $masker = new EmailMasker;

        $this->assertTrue($masker->canHandle('email'));
        $this->assertFalse($masker->canHandle('name'));

        $result = $masker->mask('test@example.com', ['type' => 'email']);
        $this->assertNotEquals('test@example.com', $result);
        $this->assertStringContainsString('@', $result);
    }

    /**
     * Test name masker.
     */
    public function test_name_masker(): void
    {
        $masker = new NameMasker;

        $this->assertTrue($masker->canHandle('name'));
        $this->assertTrue($masker->canHandle('firstName'));
        $this->assertTrue($masker->canHandle('lastName'));
        $this->assertFalse($masker->canHandle('email'));

        // Test full name
        $result = $masker->mask('John Doe', ['type' => 'name']);
        $this->assertNotEquals('John Doe', $result);
        $this->assertIsString($result);

        // Test first name
        $result = $masker->mask('John', ['type' => 'firstName']);
        $this->assertNotEquals('John', $result);
        $this->assertIsString($result);

        // Test last name
        $result = $masker->mask('Doe', ['type' => 'lastName']);
        $this->assertNotEquals('Doe', $result);
        $this->assertIsString($result);
    }

    /**
     * Test phone masker.
     */
    public function test_phone_masker(): void
    {
        $masker = new PhoneMasker;

        $this->assertTrue($masker->canHandle('phone'));
        $this->assertFalse($masker->canHandle('email'));

        // Test standard phone
        $result = $masker->mask('123-456-7890', ['type' => 'phone']);
        $this->assertNotEquals('123-456-7890', $result);
        $this->assertIsString($result);

        // Test with format
        $result = $masker->mask('123-456-7890', [
            'type' => 'phone',
            'format' => '###-###-####',
        ]);
        $this->assertMatchesRegularExpression('/^\d{3}-\d{3}-\d{4}$/', $result);
    }

    /**
     * Test address masker.
     */
    public function test_address_masker(): void
    {
        $masker = new AddressMasker;

        $this->assertTrue($masker->canHandle('address'));
        $this->assertFalse($masker->canHandle('email'));

        $result = $masker->mask('123 Main St, Anytown, USA', ['type' => 'address']);
        $this->assertNotEquals('123 Main St, Anytown, USA', $result);
        $this->assertIsString($result);
    }

    /**
     * Test location masker.
     */
    public function test_location_masker(): void
    {
        $masker = new LocationMasker;

        $this->assertTrue($masker->canHandle('city'));
        $this->assertTrue($masker->canHandle('country'));
        $this->assertTrue($masker->canHandle('postcode'));
        $this->assertFalse($masker->canHandle('email'));

        // Test city
        $result = $masker->mask('New York', ['type' => 'city']);
        $this->assertNotEquals('New York', $result);
        $this->assertIsString($result);

        // Test country
        $result = $masker->mask('USA', ['type' => 'country']);
        $this->assertNotEquals('USA', $result);
        $this->assertIsString($result);

        // Test postcode
        $result = $masker->mask('12345', ['type' => 'postcode']);
        $this->assertNotEquals('12345', $result);
        $this->assertIsString($result);
    }

    /**
     * Test text masker.
     */
    public function test_text_masker(): void
    {
        $masker = new TextMasker;

        $this->assertTrue($masker->canHandle('text'));
        $this->assertFalse($masker->canHandle('email'));

        // Test standard text
        $result = $masker->mask('Lorem ipsum dolor sit amet', ['type' => 'text']);
        $this->assertNotEquals('Lorem ipsum dolor sit amet', $result);
        $this->assertIsString($result);

        // Test with length
        $result = $masker->mask('Lorem ipsum', [
            'type' => 'text',
            'length' => 20,
        ]);
        $this->assertLessThanOrEqual(20, strlen($result));
    }

    /**
     * Test number masker.
     */
    public function test_number_masker(): void
    {
        $masker = new NumberMasker;

        $this->assertTrue($masker->canHandle('number'));
        $this->assertTrue($masker->canHandle('randomNumber'));
        $this->assertFalse($masker->canHandle('email'));

        // Test standard number
        $result = $masker->mask(123, ['type' => 'number']);
        $this->assertIsInt($result);

        // Test with min/max
        $result = $masker->mask(123, [
            'type' => 'randomNumber',
            'min' => 500,
            'max' => 1000,
        ]);
        $this->assertGreaterThanOrEqual(500, $result);
        $this->assertLessThanOrEqual(1000, $result);
    }

    /**
     * Test date masker.
     */
    public function test_date_masker(): void
    {
        $masker = new DateMasker;

        $this->assertTrue($masker->canHandle('date'));
        $this->assertTrue($masker->canHandle('datetime'));
        $this->assertTrue($masker->canHandle('time'));
        $this->assertFalse($masker->canHandle('email'));

        // Test date
        $result = $masker->mask('2023-01-01', ['type' => 'date']);
        $this->assertNotEquals('2023-01-01', $result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);

        // Test datetime
        $result = $masker->mask('2023-01-01 12:00:00', [
            'type' => 'datetime',
            'format' => 'Y-m-d H:i:s',
        ]);
        $this->assertNotEquals('2023-01-01 12:00:00', $result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);

        // Test time
        $result = $masker->mask('12:00:00', [
            'type' => 'time',
            'format' => 'H:i:s',
        ]);
        $this->assertNotEquals('12:00:00', $result);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $result);
    }

    /**
     * Test pattern masker.
     */
    public function test_pattern_masker(): void
    {
        $masker = new PatternMasker;

        $this->assertTrue($masker->canHandle('numerify'));
        $this->assertTrue($masker->canHandle('lexify'));
        $this->assertTrue($masker->canHandle('bothify'));
        $this->assertTrue($masker->canHandle('regexify'));
        $this->assertFalse($masker->canHandle('email'));

        // Test numerify
        $result = $masker->mask('123', [
            'type' => 'numerify',
            'format' => '###-##-####',
        ]);
        $this->assertMatchesRegularExpression('/^\d{3}-\d{2}-\d{4}$/', $result);

        // Test lexify
        $result = $masker->mask('ABC', [
            'type' => 'lexify',
            'format' => '????',
        ]);
        $this->assertMatchesRegularExpression('/^[a-z]{4}$/i', $result);

        // Test bothify
        $result = $masker->mask('ABC123', [
            'type' => 'bothify',
            'format' => '##??',
        ]);
        $this->assertMatchesRegularExpression('/^\d{2}[a-z]{2}$/i', $result);

        // Test regexify
        $result = $masker->mask('test@example.com', [
            'type' => 'regexify',
            'regex' => '[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}',
        ]);
        $this->assertNotEquals('test@example.com', $result);
        $this->assertMatchesRegularExpression('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $result);
    }

    /**
     * Test credit card masker.
     */
    public function test_credit_card_masker(): void
    {
        $masker = new CreditCardMasker;

        $this->assertTrue($masker->canHandle('creditCardNumber'));
        $this->assertFalse($masker->canHandle('email'));

        $result = $masker->mask('4111111111111111', ['type' => 'creditCardNumber']);
        $this->assertNotEquals('4111111111111111', $result);
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d+$/', $result);
    }

    /**
     * Test company masker.
     */
    public function test_company_masker(): void
    {
        $masker = new CompanyMasker;

        $this->assertTrue($masker->canHandle('company'));
        $this->assertTrue($masker->canHandle('url'));
        $this->assertFalse($masker->canHandle('email'));

        // Test company
        $result = $masker->mask('Acme Inc', ['type' => 'company']);
        $this->assertNotEquals('Acme Inc', $result);
        $this->assertIsString($result);

        // Test url
        $result = $masker->mask('https://example.com', ['type' => 'url']);
        $this->assertNotEquals('https://example.com', $result);
        $this->assertIsString($result);
        $this->assertStringContainsString('.', $result);
    }

    /**
     * Test IP address masker.
     */
    public function test_ip_address_masker(): void
    {
        $masker = new IpAddressMasker;

        $this->assertTrue($masker->canHandle('ipv4'));
        $this->assertTrue($masker->canHandle('ipv6'));
        $this->assertFalse($masker->canHandle('email'));

        // Test IPv4
        $result = $masker->mask('192.168.1.1', ['type' => 'ipv4']);
        $this->assertNotEquals('192.168.1.1', $result);
        $this->assertMatchesRegularExpression('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $result);

        // Test IPv6
        $result = $masker->mask('2001:0db8:85a3:0000:0000:8a2e:0370:7334', ['type' => 'ipv6']);
        $this->assertNotEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $result);
        $this->assertStringContainsString(':', $result);
    }

    /**
     * Test UUID masker.
     */
    public function test_uuid_masker(): void
    {
        $masker = new UuidMasker;

        $this->assertTrue($masker->canHandle('uuid'));
        $this->assertFalse($masker->canHandle('email'));

        $result = $masker->mask('123e4567-e89b-12d3-a456-426614174000', ['type' => 'uuid']);
        $this->assertNotEquals('123e4567-e89b-12d3-a456-426614174000', $result);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $result);
    }

    /**
     * Test password masker.
     */
    public function test_password_masker(): void
    {
        $masker = new PasswordMasker;

        $this->assertTrue($masker->canHandle('password'));
        $this->assertFalse($masker->canHandle('email'));

        $result = $masker->mask('password123', ['type' => 'password']);
        $this->assertNotEquals('password123', $result);
        $this->assertIsString($result);
        $this->assertStringStartsWith('$2y$', $result); // Check for bcrypt format
    }

    /**
     * Test default masker.
     */
    public function test_default_masker(): void
    {
        $masker = new DefaultMasker;

        // Default masker should handle anything
        $this->assertTrue($masker->canHandle('email'));
        $this->assertTrue($masker->canHandle('nonexistent'));
        $this->assertTrue($masker->canHandle('default'));

        $result = $masker->mask('test value', ['type' => 'nonexistent']);
        $this->assertNotEquals('test value', $result);
        $this->assertIsString($result);
    }
}
