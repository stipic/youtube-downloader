<?php

namespace App\Command;

use App\Entity\Queue;
use App\Repository\UserRepository;
use App\Service\Downloader\YoutubeAPI;
use App\Service\Downloader\DownloadManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriberRefreshDownloadCommand extends Command
{
    private $_userRepo;

    private $_youtube; 

    private $_downloadManager;

    public function __construct(
        UserRepository $userRepository,
        YoutubeAPI $yt,
        DownloadManager $downloadManager
    )
    {
        parent::__construct();
        $this->_userRepo = $userRepository;
        $this->_youtube = $yt->getClient();
        $this->_downloadManager = $downloadManager;
    }

    protected static $defaultName = 'subscriber:refresh:download';

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            // ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            // ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // $arg1 = $input->getArgument('arg1');

        $channels = [];
        $users = $this->_userRepo->findAll();
        foreach($users as $user)
        {
            $userSubscribed = $user->getChannels()->getValues();
            foreach($userSubscribed as $channel)
            {
                $channels[] = [
                    'channel' => $channel,
                    'user' => $user
                ];
            }
        }

        // Popis kanala za koje moramo dohvatiti nove pjesme -> $channels

        foreach($channels as $channel)
        {
            $videos = $this->_youtube->getPlaylistItemsByPlaylistId($channel['channel']->getVideos());
            $this->_downloadManager->addPlaylistToDownloadQueue($videos, Queue::ADDED_BY_CHANNEL_SUBSCRIBE_CRONJOB, $channel['user']);
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}
