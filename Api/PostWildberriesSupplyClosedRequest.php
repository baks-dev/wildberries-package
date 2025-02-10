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
use InvalidArgumentException;

final class PostWildberriesSupplyClosedRequest extends Wildberries
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
     * @see https://dev.wildberries.ru/openapi/orders-fbs/#tag/Postavki-FBS/paths/~1api~1v3~1supplies~1{supplyId}~1deliver/patch
     *
     */
    public function close(): bool
    {
        if($this->isExecuteEnvironment() === false)
        {
            $this->logger->critical('Запрос может быть выполнен только в PROD окружении', [self::class.':'.__LINE__]);
            return true;
        }

        if($this->supply === null)
        {
            throw new InvalidArgumentException(
                'Не указан идентификатор поставки через вызов метода withSupply: ->withSupply("WB-GI-1234567")'
            );
        }

        $response = $this->marketplace()->TokenHttpClient()->request(
            'PATCH',
            sprintf('/api/v3/supplies/%s/deliver', $this->supply),
        );

        if($response->getStatusCode() !== 204)
        {
            $this->logger->critical(
                sprintf('wildberries-package: Ошибка при закрытии поставки %s', $this->supply),
                [$response->toArray(false), self::class.':'.__LINE__]
            );

            return false;
        }

        return true;
    }

}