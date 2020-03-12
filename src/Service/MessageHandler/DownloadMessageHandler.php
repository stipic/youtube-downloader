<?php

namespace App\Service\MessageHandler;

use App\Entity\Song;
use App\Entity\User;
use App\Entity\Queue;
use App\Entity\Notification;
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
            $thumbUrl = $queue->getThumb();
            $runIndex = $queue->getRunIndex();

            $video = json_decode($video);
            $rootDir = $this->_appKernel->getProjectDir() . '/public';

            $song = $this->_songRepo->findOneBy([
                'ytId' => $videoId
            ]);

            $notification = new Notification();
            $notificationTitle = 'Nova pjesma dodana u profil: ' . $video->snippet->title;
            $notificationDescription = 'Pjesma je uspješno preuzeta i dodana na tvoj profil.';
            
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
                    '--no-playlist',
                    '--match-filter',
                    '!is_live'
                ]);

                $process->setTimeout(180); // MAX. 3 minute.
                $process->start();
                $process->wait();

                if($this->_fileSystem->exists($location))
                {
                    $dbPath = str_replace($rootDir, '', $location);
                    $size = \filesize($location);

                    $ytVideoDuration = isset($video->contentDetails->duration) ? self::ISO8601ToSeconds($video->contentDetails->duration) : 0;
                    if($ytVideoDuration == 0)
                    {
                        // ffmpeg pomozi :D

                        $time = exec("ffmpeg -i " . escapeshellarg($location) . " 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");
                        list($hms, $milli) = explode('.', $time);
                        list($hours, $minutes, $seconds) = explode(':', $hms);
                        $ytVideoDuration = ($hours * 3600) + ($minutes * 60) + $seconds;
                    }

                    $song = new Song();
                    $song->setYtId($videoId);
                    $song->setTitle($video->snippet->title);
                    $song->setPath($dbPath);
                    $song->addUser($user);
                    $song->setSize($size);
                    $song->setDuration($ytVideoDuration);
                    $song->setThumb($thumbUrl);
                    
                    $user->addSong($song);

                    $this->_em->persist($song);
                    $this->_em->persist($user);

                    $queue->setStatus(1); // success
                    $this->_em->persist($queue);

                    $notificationDescription = 'Pjesma nije pronađena na serveru, uspješno je preuzeta i spremljena.';
                } 
                else // else = ERROR.
                {
                    $queue->setStatus(2); // error
                    $notificationTitle = 'Neuspješno dodavanje pjesme u profil ' . $video->snippet->title;
                    $notificationDescription = 'Server nije uspio preuzeti pjesmu i dodati je u tvoj profil. Može biti puno razloga, jedan od čestih je da je pjesma uklonjena.';
                }

                echo "\n\r" . $url . ' DOWNLOAD FINISH!';

            }
            else
            {
                $notificationDescription = 'Pjesma je pronađena na serveru, neki drugi korisnik ju je već preuzeo stoga smo je dodali u tvoj profil. Ova pjesma neće biti vidljiva na listi završenih preuzimanja u tvom profilu.';
            }

            $notificationDescription .= self::runIndexToDesc($queue);
            $notification->setTitle($notificationTitle);
            $notification->setCreatedAt(new \DateTime('now'));
            $notification->setUser($user);
            $notification->setDescription($notificationDescription);

            $this->_em->persist($notification);

            $queue->setFinished(1);
            $queue->setFinishedAt(new \DateTime('now'));
            $this->_em->flush();
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

    public static function runIndexToDesc(Queue $queue)
    {
        $runIndex = $queue->getRunIndex();
        if($runIndex == Queue::ADDED_BY_DOWNLOAD_SINGLE_SONG)
        {
            return 'Pjesma se našla u tvom profilu tako što si pokrenuo download single pjesme.';
        }
        else if($runIndex == Queue::ADDED_BY_DOWNLOAD_PLAYLIST)
        {
            $video = $queue->getYoutubeInfo();
            $video = json_decode($video);
            $playlistId = $video->snippet->playlistId;
            $channelTitle = $video->snippet->channelTitle;
            $channelId = $video->snippet->channelId;

            return 'Pjesma se našla u tvom profilu tako što si pokrenuo download playliste ' . $playlistId . ' koju je kreirao kanal ' . $channelTitle . ' (' . $channelId . ').';
        }
        else if($runIndex == Queue::ADDED_BY_CHANNEL_SUBSCRIBE)
        {
            $video = $queue->getYoutubeInfo();
            $video = json_decode($video);
            $channelTitle = $video->snippet->channelTitle;
            $channelId = $video->snippet->channelId;

            return 'Pjesma se našla u tvom profilu tako što napravio pretplatu na kanal ' . $channelTitle . ' (' . $channelId . ') i automatski su se preuzele pjesme s tog kanala.';
        }
        else if($runIndex == Queue::ADDED_BY_CHANNEL_SUBSCRIBE_CRONJOB)
        {
            $video = $queue->getYoutubeInfo();
            $video = json_decode($video);
            $channelTitle = $video->snippet->channelTitle;
            $channelId = $video->snippet->channelId;

            return 'Pjesma se našla u tvom profilu tako što napravio pretplatu na kanal ' . $channelTitle . ' (' . $channelId . ') i server nakon nekog vremena dohvaća nove pjesme s tog kanala i automatski ih preuzima i dodaje u tvoj profil.';
        }

        return '#nepoznato :(';
    }
}
