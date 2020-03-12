<?php 
namespace App\Service\Downloader;

use App\Entity\Song;
use App\Entity\Queue;
use App\Entity\Notification;
use App\Repository\SongRepository;
use App\Service\Downloader\YoutubeAPI;
use App\Service\Message\DownloadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Service\MessageHandler\DownloadMessageHandler;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DownloadManager
{
    private $_songRepo;

    private $_tokenStorage;

    private $_em;

    private $_appKernel;

    private $_bus;

    private $_youtube;

    private $_youtubeHelper;

    public function __construct(
        SongRepository $songRepo,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em,
        KernelInterface $appKernel,
        MessageBusInterface $bus,
        YoutubeAPI $ytApi
    )
    {
        $this->_songRepo = $songRepo;
        $this->_tokenStorage = $tokenStorage;
        $this->_em = $em;
        $this->_appKernel = $appKernel;
        $this->_bus = $bus;
        $this->_youtube = $ytApi->getClient();
        $this->_youtubeHelper = $ytApi;
    }

    public function addPlaylistToDownloadQueue($videos, $runIndex, $user = null)
    {
        foreach($videos as $ytSong)
        {
            $url = 'https://www.youtube.com/watch?v=' . $ytSong->snippet->resourceId->videoId;
            $this->addToDownloadQueue($url, $runIndex, $ytSong, $user); 
        }
        
        return; //@todo vrati nešto
    }

    public function addToDownloadQueue($url, $runIndex, $video = null, $user = null)
    {
        $videoId = $this->_youtubeHelper->getId($url);
        $song = $this->_songRepo->findOneBy(['ytId' => $videoId]);

        if($user == null)
        {
            $user = $this->_tokenStorage->getToken()->getUser();
        }
        
        if($song instanceof Song)
        {
            // Pjesma postoji, attachaj ju na usera.

            $notification = new Notification();
            $notificationTitle = 'Nova pjesma dodana u profil: ' . $song->getTitle();
            $notificationDescription = 'Pjesma je uspješno preuzeta i dodana na tvoj profil.';
            $notificationDescription = 'Pjesma je pronađena na serveru, neki drugi korisnik ju je već preuzeo stoga smo je dodali u tvoj profil. Ova pjesma neće biti vidljiva na listi završenih preuzimanja u tvom profilu.';            
            // $notificationDescription .= DownloadMessageHandler::runIndexToDesc($runIndex); //@todo nemamo queue ovdje, što sada? kako fino ispisati description? možemo pronaći tuđi queue
            $notification->setTitle($notificationTitle);
            $notification->setCreatedAt(new \DateTime('now'));
            $notification->setUser($user);
            $notification->setDescription($notificationDescription);

            $this->_em->persist($notification);

            $user->addSong($song);
            $song->addUser($user);
            $this->_em->persist($user);
            $this->_em->persist($song);
            $this->_em->flush();
        }
        else
        {
            // Pjesma ne postoji dodaj je u queue za download i nakon toga je attachaj na usera.

            $ytVideoId = isset($video->snippet->resourceId->videoId) ? $video->snippet->resourceId->videoId : false;
            $ytVideoTitle = isset($video->snippet->title) ? $video->snippet->title : false;
            $ytThumb = isset($video->snippet->thumbnails->standard->url) ? $video->snippet->thumbnails->standard->url : '';
            if($ytThumb == '')
            {
                $ytThumb = isset($video->snippet->thumbnails->default->url) ? $video->snippet->thumbnails->default->url : '';
            }
            
            // $ytVideoDuration = $video->snippet->duration;
            if($video == null)
            {
                // u pitanju je single song download.

                $video = $this->_youtube->getVideoInfo($videoId);

                $ytVideoId = isset($video->id) ? $video->id : false;
                $ytVideoTitle = isset($video->snippet->title) ? $video->snippet->title : false;
                $ytThumb = isset($video->snippet->thumbnails->standard->url) ? $video->snippet->thumbnails->standard->url : $video->snippet->thumbnails->default->url;
            }

            if($ytVideoId == $videoId && $ytVideoTitle != false)
            {
                $rootDir = $this->_appKernel->getProjectDir();
                $safeFilename = self::normalizeString($video->snippet->title);
                $location = $rootDir . '/public/storage/song/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . date('H') . '/' . date('i') . '/' . $safeFilename . '.mp3';
                $videoInfo = json_encode($video);

                $queue = new Queue();
                $queue->setYoutubeInfo($videoInfo);
                $queue->setUrl($url);
                $queue->setVideoId($videoId);
                $queue->setLocation($location);
                $queue->setUser($user);
                $queue->setFinished(0);
                $queue->setStatus(0); // on hold
                $queue->setThumb($ytThumb);
                $queue->setRunIndex($runIndex);
                $queue->setCreatedAt(new \DateTime('now'));

                $this->_em->persist($queue);
                $this->_em->flush();

                $message = new DownloadMessage($queue->getId());
                $envelope = new Envelope($message);
                $this->_bus->dispatch(
                    $envelope->with(
                        new SerializerStamp(
                            [
                                'groups' => ['data'],
                            ]
                        )
                    )
                );
            }
        }

        return; //@todo daj neki status.
    }

    public static function normalizeString($str = '')
    {
        $str = strip_tags($str); 
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode( $str, ENT_QUOTES, "utf-8" );
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        $str = str_replace(' ', '-', $str);
        $str = rawurlencode($str);
        $str = str_replace('%', '-', $str);
        return $str;
    }
}