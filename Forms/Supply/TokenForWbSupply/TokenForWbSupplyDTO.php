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

declare(strict_types=1);

namespace BaksDev\Wildberries\Package\Forms\Supply\TokenForWbSupply;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Type\id\WbTokenUid;
use Symfony\Component\Validator\Constraints as Assert;

final class TokenForWbSupplyDTO
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private WbTokenUid $token;

    /** @var array<int, WbTokenUid> $tokens */
    private array $tokens;

    private UserProfileUid $profile;

    public function __construct(
        UserProfileUid $profile,
        array $tokens
    )
    {
        $this->profile = $profile;
        $this->tokens = $tokens;
    }

    public function getToken(): WbTokenUid
    {
        return $this->token;
    }

    public function setToken(WbTokenUid $token): void
    {
        $this->token = $token;
    }

    /** @return array<int, WbTokenUid> */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }
}