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

namespace BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;

use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\Collection\WbPackageStatusInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('baks.wb.package.status')]
final class WbPackageStatusError implements WbPackageStatusInterface
{
    /**
     * Ошибка при добавлении заказа в поставку Wildberries
     */
    public const string STATUS = 'error';

    public function __toString(): string
    {
        return self::STATUS;
    }

    /**
     * Возвращает значение (value)
     */
    public function getValue(): string
    {
        return self::STATUS;
    }


    /**
     * Проверяет, относится ли строка к данному объекту
     */
    public static function equals(string $status): bool
    {
        return mb_strtolower($status) === self::STATUS;
    }
}