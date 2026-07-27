<?php

namespace App\Interface;

use App\Entity\User;

interface UserAwareInterface
{
    public function getVeterinarian(): ?User;
    public function setVeterinarian(?User $veterinarian): static;
}
