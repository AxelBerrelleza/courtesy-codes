<?php

namespace App\Factory;

use App\Entity\Code;
use App\Enum\CodeStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Code>
 */
final class CodeFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct() {}

    #[\Override]
    public static function class(): string
    {
        return Code::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'event' => EventFactory::new(),
            'uuid' => self::faker()->uuid(),
            'quantity' => self::faker()->randomNumber(),
            'type' => self::faker()->text(32),
            'zoneId' => self::faker()->text(255),
            'status' => CodeStatus::ACTIVE,
            'expiresAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('tomorrow', '+7 days')),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Code $code): void {})
        ;
    }
}
