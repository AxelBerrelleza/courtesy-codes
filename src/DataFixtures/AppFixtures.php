<?php

namespace App\DataFixtures;

use App\Enum\UserRoles;
use App\Factory\CodeFactory;
use App\Factory\EventFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        EventFactory::createMany(number: 3);
        UserFactory::createOne([
            'roles' => [UserRoles::ADMIN],
            'accessToken' => 'sk_test_admin_000000'
        ]);
        UserFactory::createOne([
            'roles' => [UserRoles::PROMOTER],
            'accessToken' => 'sk_test_promoter_abc123'
        ]);
        UserFactory::createOne([
            'accessToken' => 'sk_test_user_12345'
        ]);

        CodeFactory::createMany(number: 3);

        $manager->flush();
    }
}
