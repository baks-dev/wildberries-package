<?php

namespace BaksDev\Wildberries\Products\Type\Settings\Event;

use App\Kernel;
use BaksDev\Core\Type\UidType\Uid;
use Symfony\Component\Uid\AbstractUid;

final class WbProductSettingsEventUid extends Uid
{
    public const TEST = '018ad83b-22ca-71f4-b1b5-12c89ede837f';

    public const TYPE = 'wb_product_settings_event';

}