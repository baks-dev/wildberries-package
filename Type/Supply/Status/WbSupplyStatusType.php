<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\Type\Supply\Status;

use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

final class WbSupplyStatusType extends StringType
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return $value instanceof WbSupplyStatus ? $value->getWbSupplyStatus() : $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        foreach ($this->getDeclaredType() as $status) {
            if ($status::STATUS === $value) {
                return new WbSupplyStatus(new $status());
            }
        }

        throw new InvalidArgumentException(sprintf('Not found WbSupplyStatus %s', $value));
    }

    public function getName(): string
    {
        return WbSupplyStatus::TYPE;
    }

    public function getDeclaredType(): array
    {
        return array_filter(
            get_declared_classes(),
            static function ($className) {
                return in_array(WbSupplyStatusInterface::class, class_implements($className), true);
            }
        );
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = 15;

        return $platform->getStringTypeDeclarationSQL($column);
    }
}
