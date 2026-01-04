<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

use BaksDev\Wildberries\Api\Wildberries;
use DomainException;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)] /* TODO: удалить !!! */
final class FindAllWildberriesSupplyRequest extends Wildberries
{
    private int $next = 0;

    /**
     * Наименование поставки
     */
    private ?string $name = null;


    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Получить список поставок (LIMIT 100)
     *
     * @see https://dev.wildberries.ru/openapi/orders-fbs/#tag/Postavki-FBS/paths/~1api~1v3~1supplies/get
     *
     * @return Generator<WildberriesSupplyDTO>
     *
     */
    public function all(int $next = 0): Generator
    {
        $data = [
            "limit" => 100,
            "next" => $next
        ];

        $response = $this->marketplace()->TokenHttpClient()->request(
            'GET',
            '/api/v3/supplies',
            ['query' => $data],
        );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            throw new DomainException(
                message: $response->getStatusCode().': '.$content['message'] ?? self::class,
                code: $response->getStatusCode()
            );
        }

        $this->next += 100;

        $response->toArray(false);

        foreach($content['supplies'] as $data)
        {
            yield new WildberriesSupplyDTO($data);
        }
    }

    /**
     * Next
     */
    public function getNext(): int
    {
        return $this->next;
    }

}