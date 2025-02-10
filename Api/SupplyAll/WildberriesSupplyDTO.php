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

namespace BaksDev\Wildberries\Package\Api\SupplyAll;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;


final class WildberriesSupplyDTO
{
    /**
     * Идентификатор поставки
     * @example "WB-GI-1234567"
     */
    private string $identifier;

    /** Наименование поставки */
    private string $name;

    /** Флаг закрытия поставки */
    private bool $done;

    /** Дата создания поставки */
    private DateTimeImmutable $created;

    /** Дата закрытия поставки */
    private ?DateTimeImmutable $closed;

    /** Дата скана поставки */
    private ?DateTimeImmutable $scan;

    /**
     * Тип поставки:
     *
     * 0 - признак отсутствует
     * 1 - обычная
     * 2 - СГТ (Содержит сверхгабаритные товары)
     * 3 - КГТ (Содержит крупногабаритные товары). Не используется на данный момент.
     */
    private int $cargo;

    public function __construct(array $content)
    {

        $this->identifier = $content['id'];
        $this->name = $content['name'];
        $this->done = $content['done'];
        $this->created = new DateTimeImmutable($content['createdAt']);
        $this->closed = $content['closedAt'] ? new DateTimeImmutable($content['closedAt']) : null;
        $this->scan = $content['scanDt'] ? new DateTimeImmutable($content['scanDt']) : null;
        $this->cargo = $content['cargoType'];
    }

    /**
     * Identifier
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Done
     */
    public function isDone(): bool
    {
        return $this->done;
    }

    /**
     * Created
     */
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * Closed
     */
    public function getClosed(): DateTimeImmutable
    {
        return $this->closed;
    }

    /**
     * Scan
     */
    public function getScan(): DateTimeImmutable
    {
        return $this->scan;
    }

    /**
     * Cargo
     */
    public function getCargo(): int
    {
        return $this->cargo;
    }


}