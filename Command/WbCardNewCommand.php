<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BaksDev\Wildberries\Products\Command;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Products\Messenger\WbCardNew\WbCardNewMessage;
use BaksDev\Wildberries\Repository\AllProfileToken\AllProfileTokenInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Получаем карточки товаров и добавляем отсутствующие
 */
#[AsCommand(
    name: 'baks:wildberries-products:new',
    description: 'Получаем карточки товаров и добавляем отсутствующие товары')
]
class WbCardNewCommand extends Command
{
    private AllProfileTokenInterface $allProfileToken;
    private MessageDispatchInterface $messageDispatch;

    public function __construct(
        MessageDispatchInterface $messageDispatch,
        AllProfileTokenInterface $allProfileToken,
    )
    {
        parent::__construct();
        $this->messageDispatch = $messageDispatch;
        $this->allProfileToken = $allProfileToken;
    }

    protected function configure(): void
    {
        $this->addArgument('profile', InputArgument::OPTIONAL, 'Идентификатор профиля');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);

        $profile = $input->getArgument('profile');


        if($profile)
        {
            /** Если требуется выбрать профиль из списка */
            if($profile === 'choice')
            {
                $helper = $this->getHelper('question');

                $profiles = $this->allProfileToken->fetchAllWbTokenProfileAssociative();

                $questions = null;

                foreach($profiles as $quest)
                {
                    $questions[] = $quest->getAttr();
                }

                $question = new Question('Профиль пользователя: ');
                $question->setAutocompleterValues($questions);

                $profileName = $helper->ask($input, $output, $question);

                foreach($profiles as $profile)
                {
                    if($profile->getAttr() === $profileName)
                    {
                        break;
                    }
                }
            }

            /* Присваиваем профиль пользователя */
            $profile = new UserProfileUid($profile);

            /* Отправляем сообщение в шину профиля */
            $this->messageDispatch->dispatch(
                message: new WbCardNewMessage($profile),
                transport: (string) $profile,
            );
        }
        else
        {
            foreach($this->allProfileToken->fetchAllWbTokenProfileAssociative() as $profile)
            {
                /* Отправляем сообщение в шину профиля */
                $this->messageDispatch->dispatch(
                    message: new WbCardNewMessage($profile),
                    transport: (string) $profile,
                );
            }
        }

        $io->success('Карточки успешно добавлены в очередь');

        return Command::SUCCESS;
    }

}