<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Wildberries\Package\Repository\Supply\LastWbSupply;

use BaksDev\Wildberries\Package\Type\Supply\Event\WbSupplyEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class LustWbSupplyResult
{
    public function __construct(
        private int $total,
        private string $id,
        private string $event,
        private string $status,
        private string $supply_date,
        private string|null $identifier,
        private string|null $token = null,
        private string|null $token_name = null,
    ) {}

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getId(): WbSupplyUid
    {
        return new WbSupplyUid($this->id);
    }

    public function getEvent(): WbSupplyEventUid
    {
        return new WbSupplyEventUid($this->event);
    }

    public function getStatus(): WbSupplyStatus
    {
        return new WbSupplyStatus($this->status);
    }

    public function getSupplyDate(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->supply_date);
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getToken(): ?Uuid
    {
        return null !== $this->token ? new Uuid($this->token) : null;
    }

    public function getTokenName(): ?string
    {
        return $this->token_name;
    }
}