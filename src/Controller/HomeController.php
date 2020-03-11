<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Song;
use App\Repository\ChannelRepository;
use App\Repository\QueueRepository;
use App\Repository\SongRepository;
use App\Service\Downloader\DownloadManager;
use App\Service\Downloader\YoutubeAPI;
use App\Service\Message\DownloadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    private $_youtube; 

    private $_youtubeHelper;

    public function __construct(
        YoutubeAPI $yt
    )
    {
        $this->_youtube = $yt->getClient();
        $this->_youtubeHelper = $yt;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(
        QueueRepository $queueRepo
    )
    {
        //@todo izračunati koliko je queue-ava ISPRED tvog prvog na redu.

        $onHold = $queueRepo->findBy([
            'user' => $this->getUser()->getId(),
            'finished' => 0,
            'status' => 0
        ]);

        $finished = $queueRepo->findBy([
            'user' => $this->getUser()->getId(),
            'finished' => 1,
            'status' => 1
        ]);

        $failed = $queueRepo->findBy([
            'user' => $this->getUser()->getId(),
            'status' => 2
        ]);

        $onHoldBefore = $queueRepo->findQueueNumber($this->getUser()->getId());

        // dashboard.
        return $this->render('home/index.html.twig', [
            'onHold' => count($onHold),
            'finished' => count($finished),
            'failed' => count($failed),
            'waitUntilFirst' => (int) $onHoldBefore['num']
        ]);
    }

    /**
     * @Route("/channel/subscribe", name="channelSubscribe")
     */
    public function channelSubscribe(
        Request $request,
        EntityManagerInterface $em,
        ChannelRepository $channelRepository,
        DownloadManager $downloadManager
    )
    {
        $channelUrl = $request->get('channel');
        $channel = $this->_youtube->getChannelFromURL($channelUrl);
        $user = $this->getUser();

        if(isset($channel->id))
        {
            $dbChannel = $channelRepository->findOneBy(['ytId' => $channel->id]);
            $playlistId = $channel->contentDetails->relatedPlaylists->uploads;

            if(!$dbChannel instanceof Channel)
            {
                // Kanal ne postoj, kreiraj ga i attachaj usera.

                $xChannel = new Channel();
                $xChannel->setYtId($channel->id);
                $xChannel->setTitle($channel->snippet->title);
                $xChannel->addSubscriber($user);
                $xChannel->setVideos($playlistId);

                $user->addChannel($xChannel);
                $em->persist($xChannel);
                $em->persist($user);
            }
            else 
            {
                // Kanal već postoji, attachaj usera.

                $dbChannel->addSubscriber($user);
                $user->addChannel($dbChannel);
                $em->persist($dbChannel);
                $em->persist($user);
            }

            $em->flush();

            $videos = $this->_youtubeHelper->getPlaylistVideos($playlistId);
            $downloadManager->addPlaylistToDownloadQueue($videos);
        }

        return new RedirectResponse($this->generateUrl('home'));
    }

    /**
     * @Route("/download-playlist", name="downloadPlaylist")
     */
    public function downloadPlaylist(
        Request $request,
        DownloadManager $downloadManager
    )
    {
        // Uzimamo playlistu, dohvacamo popis svih videa u njoj i šaljemo u download queue jednu po jednu.
        
        $vid = $request->get('playlist');
        $playlistId = $this->_youtubeHelper->getPlaylistId($vid);
        $videos = $this->_youtubeHelper->getPlaylistVideos($playlistId);

        $downloadManager->addPlaylistToDownloadQueue($videos);

        return new RedirectResponse($this->generateUrl('home'));
    }

    /**
     * @Route("/download-single-song", name="download")
     */
    public function download(
        Request $request,
        DownloadManager $downloadManager
    )
    {
        // HTTP Request za download YT pjesme (jedne. - ne playliste)

        $vid = $request->get('url');
        $downloadManager->addToDownloadQueue($vid);

        return new RedirectResponse($this->generateUrl('home'));
    }
}
