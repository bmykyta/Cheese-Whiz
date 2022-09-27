<?php

namespace App\Tests\Functional;

use App\Entity\CheeseListing;
use App\Test\CustomApiTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class CheeseListingResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateCheeseListing(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/cheeses');
        $this->assertResponseStatusCodeSame(401);

        $authedUser = $this->createUserAndLogIn($client, 'cheeseplease@example.com', 'foo');
        $otherUser = $this->createUser('otheruser@example.com', 'foo');

        $cheesyData = [
            'title' => 'Mystery cheese.... kinda green',
            'description' => 'What mysteries does it hold?',
            'price' => 5000
        ];
        $client->request('POST', '/api/cheeses', [
            'json' => $cheesyData,
        ]);
        $this->assertResponseStatusCodeSame(422);

        $client->request('POST', '/api/cheeses', [
            'json' => $cheesyData + ['owner' => '/api/users/'.$otherUser->getId()],
        ]);
        $this->assertResponseStatusCodeSame(422, 'not passing the correct owner');

        $client->request('POST', '/api/cheeses', [
            'json' => $cheesyData + ['owner' => '/api/users/'.$authedUser->getId()],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testUpdateCheeseListing()
    {
        $client = static::createClient();
        $user1 = $this->createUser('user1@example.com', 'foo');
        $user2 = $this->createUser('user2@example.com', 'foo');

        $cheeseListing = new CheeseListing('Block of cheddar');
        $cheeseListing->setOwner($user1)->setPrice(1000)->setDescription('mmm');

        $em = $this->getEntityManager();
        $em->persist($cheeseListing);
        $em->flush();

        $this->logIn($client, $user2->getEmail(), 'foo');
        $client->request('PUT', '/api/cheeses/' . $cheeseListing->getId(), [
           'json' => ['title' => 'updated', 'owner' => '/api/users/'.$user2->getId()]
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, $user1->getEmail(), 'foo');
        $client->request('PUT', '/api/cheeses/' . $cheeseListing->getId(), [
           'json' => ['title' => 'updated']
        ]);
        $this->assertResponseStatusCodeSame(200);
    }
}
