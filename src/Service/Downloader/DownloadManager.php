<?php 
namespace App\Service\Downloader;

use App\Entity\Song;
use App\Repository\SongRepository;
use App\Service\Downloader\YoutubeAPI;
use App\Service\Message\DownloadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
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

    public function addToDownloadQueue($url, $video = null)
    {
        $videoId = $this->_youtubeHelper->getId($url);
        $song = $this->_songRepo->findOneBy(['ytId' => $videoId]);
        $user = $this->_tokenStorage->getToken()->getUser();
        if($song instanceof Song)
        {
            // Pjesma postoji, attachaj ju na usera.

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
            if($video == null)
            {
                // u pitanju je single song download.

                $video = $this->_youtube->getVideoInfo($videoId);

                $ytVideoId = isset($video->id) ? $video->id : false;
                $ytVideoTitle = isset($video->snippet->title) ? $video->snippet->title : false;
            }

            if($ytVideoId == $videoId && $ytVideoTitle != false)
            {
                $rootDir = $this->_appKernel->getProjectDir();
                $safeFilename = \App\Controller\HomeController::normalizeString($video->snippet->title);
                $location = $rootDir . '/public/storage/' . $safeFilename . '.mp3';

                $message = new DownloadMessage($url, $videoId, $video, $location, $user->getId());
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
}