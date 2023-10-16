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

namespace BaksDev\Wildberries\Package\Entity\Package;

use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

use BaksDev\Core\Entity\EntityEvent;
use Exception;
use InvalidArgumentException;

/* Package */

#[ORM\Entity]
#[ORM\Table(name: 'wb_package_supply')]
#[ORM\Index(columns: ['supply'])]
class WbPackageSupply
{
    public const TABLE = 'wb_package_supply';
    
    /**
     * ID упаковки
     */
    #[ORM\Id]
    #[ORM\Column(type: WbPackageUid::TYPE)]
    private WbPackageUid $id;
    
    /**
     * ID Поставки
     */
    #[ORM\Column(type: WbSupplyUid::TYPE)]
    private WbSupplyUid $supply;
    
    
    public function __construct(WbPackageUid $id, WbSupplyUid $supply)
    {
        $this->id = $id;
        $this->supply = $supply;
    }
    
}