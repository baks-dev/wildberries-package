<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Wildberries\Products\Controller\Admin\Cards\Tests;

use BaksDev\Users\User\Tests\TestUserAccount;
use BaksDev\Wildberries\Products\Entity\Settings\WbProductSettings;
use BaksDev\Wildberries\Products\Type\Cards\Id\WbCardUid;
use BaksDev\Wildberries\Products\Type\Settings\Event\WbProductSettingsEventUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-products
 * @group wildberries-products-card
 */
#[When(env: 'test')]
final class UpdateControllerTest extends WebTestCase
{
    private const URL = '/admin/wb/product/card/update/%s';

    private const ROLE = 'ROLE_WB_CARDS_UPDATE';

    /**
     * Доступ по роли
     *
     * @depends BaksDev\Wildberries\Products\Controller\Admin\Cards\Tests\IndexControllerTest::testComplete
     * @see IndexControllerTest
     *
     */
    public function testRoleSuccessful(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getModer(self::ROLE);

            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, WbCardUid::TEST));

            self::assertResponseIsSuccessful();
        }
    }

    /**
     * Доступ по роли ROLE_ADMIN
     */
    public function testRoleAdminSuccessful(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getAdmin();

            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, WbCardUid::TEST));

            self::assertResponseIsSuccessful();
        }

    }

    /**
     * Доступ по роли ROLE_USER
     */
    public function testRoleUserDeny(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getUsr();
            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, WbCardUid::TEST));

            self::assertResponseStatusCodeSame(403);
        }

    }

    /** Доступ по без роли */
    public function testGuestFiled(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $client->request('GET', sprintf(self::URL, WbCardUid::TEST));

            // Full authentication is required to access this resource
            self::assertResponseStatusCodeSame(401);
        }

    }

    public function testComplete(): void
    {
        self::assertTrue(true);
    }
}
