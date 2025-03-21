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

namespace BaksDev\Wildberries\Package\Messenger\Supply;

use App\Kernel;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Api\PostWildberriesSupplyDeleteRequest;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 99)] // высокий приоритет - выполняется первым
final readonly class DeleteWbSupplyHandler
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private OpenWbSupplyInterface $openWbSupply,
        private PostWildberriesSupplyDeleteRequest $wildberriesSupplyDelete,
        private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
        private ORMQueryBuilder $ORMQueryBuilder
    ) {}

    /**
     * Метод удаляет поставку Wildberries если системная поставка со статусом Close (Закрыта),
     * но в ней отсутствуют заказы!
     */
    public function __invoke(WbSupplyMessage $message): void
    {
        /**
         * Получаем активное событие системной поставки
         */
        $Event = $this->wbSupplyCurrentEvent
            ->forSupply($message->getId())
            ->find();

        if(false === $Event || false === $Event->getStatus()->equals(WbSupplyStatusClose::STATUS))
        {
            return;
        }

        /** Нельзя удалить поставку с имеющимися заказами */
        if($Event->getTotal() !== 0)
        {
            return;
        }

        /* Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->openWbSupply
            ->forSupply($message->getId())
            ->find();

        if(false === ($UserProfileUid instanceof UserProfileUid) || empty($UserProfileUid->getAttr()))
        {
            return;
        }

        /* Удаляем поставку Wildberries Api */
        $isDelete = $this->wildberriesSupplyDelete
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->delete();

        if(false === $isDelete)
        {
            return;
        }

        /** Удаляем системную поставку */
        $EntityManager = $this->ORMQueryBuilder->getEntityManager();
        $WbSupply = $EntityManager->getRepository(WbSupply::class)->find($message->getId());
        $WbSupply ? $EntityManager->remove($WbSupply) : false;
        $EntityManager->flush();

        $this->logger->info(
            sprintf('%s: Удалили поставку с нулевыми заказами', $UserProfileUid->getAttr()),
            [self::class.':'.__LINE__]
        );
    }


}