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

namespace BaksDev\Wildberries\Package\Api\SupplyOpen;

use BaksDev\Wildberries\Api\Wildberries;
use DomainException;

final class WildberriesSupplyOpen extends Wildberries
{
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
     * Создать новую поставку
     *
     * Возвращает идентификатор созданной поставки в формате "WB-GI-1234567".
     *
     * @see https://openapi.wildberries.ru/marketplace/api/ru/#tag/Postavki/paths/~1api~1v3~1supplies/post
     *
     */
    public function open(): WildberriesSupplyOpenDTO
    {
        $data = ["name" => $this->name];

        $response = $this->TokenHttpClient()->request(
            'POST',
            '/api/v3/supplies',
            ['json' => $data],
        );

        if($response->getStatusCode() !== 201)
        {
            $content = $response->toArray(false);
            //$this->logger->critical('curl -X POST "' . $url . '" ' . $curlHeader . ' -d "' . $data . '"');
            throw new DomainException(
                message: $response->getStatusCode().': '.$content['message'] ?? self::class,
                code: $response->getStatusCode()
            );
        }

        return new WildberriesSupplyOpenDTO($response->toArray(false));
    }


}