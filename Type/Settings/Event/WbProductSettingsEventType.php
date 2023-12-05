<?php

namespace BaksDev\Wildberries\Products\Type\Settings\Event;


use BaksDev\Core\Type\UidType\UidType;

final class WbProductSettingsEventType extends UidType
{

    public function getClassType(): string
    {
        return WbProductSettingsEventUid::class;
    }
    
    public function getName(): string
    {
        return WbProductSettingsEventUid::TYPE;
    }
}