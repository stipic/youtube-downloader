<?php

namespace App\Service\MessageHandler;

use App\Entity\Song;
use App\Entity\User;
use App\Repository\SongRepository;
use App\Repository\UserRepository;
use Symfony\Component\Process\Process;
use App\Service\Message\DownloadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DownloadMessageHandler implements MessageHandlerInterface
{
    private $_em;

    private $_fileSystem;

    private $_songRepo;

    private $_userRepo;

    private $_appKernel;
    
    public function __construct(
        EntityManagerInterface $em,
        Filesystem $fileSystem,
        SongRepository $songRepository,
        UserRepository $userRepository,
        KernelInterface $appKernel
    )
    {
        $this->_em = $em;
        $this->_fileSystem = $fileSystem;
        $this->_songRepo = $songRepository;
        $this->_userRepo = $userRepository;
        $this->_appKernel = $appKernel;
    }
    /**
     * @param DownloadMessage.
     */
    public function __invoke(DownloadMessage $message)
    {
        $url = $message->getUrl();
        $location = $message->getLocation();
        $videoId = $message->getVideoId();
        $video = $message->getYoutubeInfo();
        $userId = $message->getUserId();
        $user = $this->_userRepo->findOneById($userId);
        $rootDir = $this->_appKernel->getProjectDir() . '/public';

        $song = $this->_songRepo->findOneBy([
            'ytId' => $videoId
        ]);
        if(!$song instanceof Song && $user instanceof User)
        {
            echo "\n\r" . 'DOWNLOAD STARTED ' . $url;

            $process = new Process(['youtube-dl', '--extract-audio', '--audio-format',  'mp3', $url, '--output', $location, '--audio-quality', 0, '--no-playlist']);
            $process->setTimeout(3600);
            $process->start();
            $process->wait();

            if($this->_fileSystem->exists($location))
            {
                try
                {
                    $dbPath = str_replace($rootDir, '', $location);

                    $song = new Song();
                    $song->setYtId($videoId);
                    $song->setTitle($video->snippet->title);
                    $song->setPath($dbPath);
                    $song->addUser($user);
                    $user->addSong($song);

                    $this->_em->persist($song);
                    $this->_em->persist($user);
                    $this->_em->flush();
                }
                catch(\Exception $e)
                {
                    echo $e->getMessage();exit;
                }
            } // else = ERROR.

            echo "\n\r" . $url . ' DOWNLOAD FINISH!';

        } // else = VIDEO VEĆ POSTOJI.
    }
}
