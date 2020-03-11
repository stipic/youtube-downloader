<?php

namespace App\Service\MessageHandler;

use App\Entity\Queue;
use App\Entity\Song;
use App\Entity\User;
use App\Repository\SongRepository;
use App\Repository\UserRepository;
use App\Repository\QueueRepository;
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

    private $_queueRepo;
    
    public function __construct(
        EntityManagerInterface $em,
        Filesystem $fileSystem,
        SongRepository $songRepository,
        UserRepository $userRepository,
        KernelInterface $appKernel,
        QueueRepository $queueRepository
    )
    {
        $this->_em = $em;
        $this->_fileSystem = $fileSystem;
        $this->_songRepo = $songRepository;
        $this->_userRepo = $userRepository;
        $this->_appKernel = $appKernel;
        $this->_queueRepo = $queueRepository;
    }
    /**
     * @param DownloadMessage.
     */
    public function __invoke(DownloadMessage $message)
    {
        $queue = $this->_queueRepo->findOneById($message->getQueueId());
        if($queue instanceof Queue)
        {
            $url = $queue->getUrl();
            $location = $queue->getLocation();
            $videoId = $queue->getVideoId();
            $video = $queue->getYoutubeInfo();
            $user = $queue->getUser();

            $video = json_decode($video);
            $rootDir = $this->_appKernel->getProjectDir() . '/public';

            $song = $this->_songRepo->findOneBy([
                'ytId' => $videoId
            ]);

            if(!$song instanceof Song && $user instanceof User)
            {
                echo "\n\r" . 'DOWNLOAD STARTED ' . $url;

                $process = new Process([
                    'youtube-dl', 
                    '--extract-audio', 
                    '--audio-format',  
                    'mp3', 
                    $url, 
                    '--output', 
                    $location, 
                    '--audio-quality', 
                    0, 
                    '--no-playlist'
                ]);

                $process->setTimeout(180); // MAX. 3 minute.
                $process->start();
                $process->wait();

                if($this->_fileSystem->exists($location))
                {
                    $dbPath = str_replace($rootDir, '', $location);
                    $size = \filesize($location);
                    $ytVideoDuration = isset($video->contentDetails->duration) ? self::ISO8601ToSeconds($video->contentDetails->duration) : 0;

                    $song = new Song();
                    $song->setYtId($videoId);
                    $song->setTitle($video->snippet->title);
                    $song->setPath($dbPath);
                    $song->addUser($user);
                    $song->setSize($size);
                    $song->setDuration($ytVideoDuration);
                    $user->addSong($song);

                    $this->_em->persist($song);
                    $this->_em->persist($user);

                    $queue->setStatus(1); // success
                    $this->_em->persist($queue);
                } 
                else // else = ERROR.
                {
                    $queue->setStatus(2); // error
                }

                $queue->setFinished(1);
                $queue->setFinishedAt(new \DateTime('now'));
                $this->_em->flush();

                echo "\n\r" . $url . ' DOWNLOAD FINISH!';

            } // else = VIDEO VEĆ POSTOJI.
        }
    }

    public static function ISO8601ToSeconds($ISO8601)
    {
        $interval = new \DateInterval($ISO8601);
    
        return ($interval->d * 24 * 60 * 60) +
            ($interval->h * 60 * 60) +
            ($interval->i * 60) +
            $interval->s;
    }
}
