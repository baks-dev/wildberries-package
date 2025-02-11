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
use DateInterval;
use InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final class GetWildberriesSupplyStickerRequest extends Wildberries
{

    /**
     * Идентификатор поставки
     * Example: WB-GI-1234567
     */
    private ?string $supply = null;

    /**
     * Тип этикетки
     * svg, zplv (вертикальный), zplh (горизонтальный), png
     */
    private $type = 'svg';


    public function withSupply(string $supply): self
    {
        $this->supply = $supply;
        return $this;
    }

    /**
     * Получить QR поставки
     * Возвращает QR в svg, zplv (вертикальный), zplh (горизонтальный), png.
     *
     * По умолчанию SVG
     *
     * @see https://dev.wildberries.ru/openapi/orders-fbs/#tag/Postavki-FBS/paths/~1api~1v3~1supplies~1{supplyId}~1barcode/get
     *
     */

    public function getSupplySticker(): string|false
    {
        if($this->supply === null)
        {
            throw new InvalidArgumentException(
                'Не указан идентификатор поставки через вызов метода withSupply: ->withSupply("WB-GI-1234567")'
            );
        }

        $cache = new FilesystemAdapter('wildberries-package');
        $key = md5($this->getProfile().$this->supply.self::class);
        //$cache->deleteItem($key);

        $file = $cache->get($key, function(ItemInterface $item): string|false {

            $item->expiresAfter(DateInterval::createFromDateString('1 second'));

            $data = ["type" => $this->type];

            $response = $this->marketplace()->TokenHttpClient()->request(
                'GET',
                '/api/v3/supplies/'.$this->supply.'/barcode',
                ['query' => $data],
            );

            $content = $response->toArray(false);

            if($response->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    sprintf('wildberries-package: Ошибка при получении QR поставки %s', $this->supply),
                    [$content, self::class.':'.__LINE__]
                );

                return false;
            }

            if(empty($content['file']))
            {
                return false;
            }

            $item->expiresAfter(DateInterval::createFromDateString('1 day'));

            return $content['file'];
        });

        return $file;
    }

    /**
     * Тип этикетки svg
     */
    public function setTypeSVG(): self
    {
        $this->type = 'svg';

        return $this;
    }


    /**
     * Тип этикетки zplv (вертикальный)
     */
    public function setTypeZPLV(): self
    {
        $this->type = 'zplv';

        return $this;
    }


    /**
     * Тип этикетки zplh (горизонтальный)
     */
    public function setTypeZPLH(): self
    {
        $this->type = 'zplh';

        return $this;
    }


    /**
     * Тип этикетки png
     */
    public function setTypePNG(): self
    {
        $this->type = 'png';

        return $this;
    }
}