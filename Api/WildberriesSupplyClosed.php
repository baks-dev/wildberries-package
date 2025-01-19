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

namespace BaksDev\Wildberries\Package\Api;

use BaksDev\Wildberries\Api\Wildberries;
use DomainException;
use InvalidArgumentException;

final class WildberriesSupplyClosed extends Wildberries
{

    /**
     * Идентификатор поставки
     * Example: WB-GI-1234567
     */
    private ?string $supply = null;


    public function withSupply(string $supply): self
    {
        $this->supply = $supply;

        return $this;
    }


    /**
     * Передать поставку в доставку
     *
     * Закрывает поставку и переводит все сборочные задания в ней в статус complete ("В доставке").
     * После закрытия поставки новые сборочные задания к ней добавить будет невозможно.
     * Передать поставку в доставку можно только при наличии в ней хотя бы одного сборочного задания.
     *
     * @see https://openapi.wildberries.ru/marketplace/api/ru/#tag/Postavki/paths/~1api~1v3~1supplies~1{supplyId}~1deliver/patch
     *
     */
    public function close(): void
    {
        if($this->supply === null)
        {
            throw new InvalidArgumentException(
                'Не указан идентификатор поставки через вызов метода withSupply: ->withSupply("WB-GI-1234567")'
            );
        }

        if($this->test)
        {
            return;
        }

        $response = $this->TokenHttpClient()->request(
            'PATCH',
            '/api/v3/supplies/'.$this->supply.'/deliver',
        );

        if($response->getStatusCode() !== 204)
        {
            $content = $response->toArray(false);
            //$this->logger->critical('curl -X POST "' . $url . '" ' . $curlHeader . ' -d "' . $data . '"');
            throw new DomainException(
                message: $response->getStatusCode().': '.$content['message'] ?? self::class,
                code: $response->getStatusCode()
            );
        }
    }

}