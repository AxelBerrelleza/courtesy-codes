<?php

namespace App\DataFixtures;

use App\Factory\EventFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        EventFactory::createMany(number: 3);
        UserFactory::createOne(['accessToken' => 'sk_test_admin_000000']);

        $manager->flush();
    }
}
