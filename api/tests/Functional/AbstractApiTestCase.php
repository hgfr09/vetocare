<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Factory\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

abstract class AbstractApiTestCase extends KernelTestCase
{
    use HasBrowser;
    protected ?User $connectedUser;

    public function browserAs(User $user): KernelBrowser
    {
        $this->connectedUser = $user;
        $jwtManager = $this->getContainer()->get(JWTTokenManagerInterface::class);
        $token = $jwtManager->create($user);

        return $this->browser()
            ->setDefaultHttpOptions(HttpOptions::create()
                ->withHeader("Authorization", 'Bearer ' . $token));
    }

    public function browserAnonymous(): KernelBrowser
    {
        $this->connectedUser = null;
        return $this->browser();
    }

    public function browserAsAdmin(?User $admin = null): KernelBrowser
    {
        return $this->browserAs($admin ?? UserFactory::createAdmin());
    }

    public function browserAsVeto(?User $veto = null): KernelBrowser
    {
        return $this->browserAs($veto ?? UserFactory::createVeterinarian());
    }

    public function patchAsVeto(string $url, array $data, ?User $veto = null): KernelBrowser
    {
        return $this->browserAsVeto($veto)->patch($url, [
            'json' => $data,
            'headers' => ['Content-Type' => 'application/merge-patch+json']
        ]);
    }

    public function patchAsAdmin(string $url, array $data, ?User $admin = null): KernelBrowser
    {
        return $this->browserAsAdmin($admin)->patch($url, [
            'json' => $data,
            'headers' => ['Content-Type' => 'application/merge-patch+json']
        ]);
    }
}
