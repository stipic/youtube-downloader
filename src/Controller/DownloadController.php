<?php

namespace App\Controller;

use App\Entity\Queue;
use App\Entity\Channel;
use App\Repository\QueueRepository;
use App\Repository\ChannelRepository;
use App\Service\Downloader\YoutubeAPI;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Downloader\DownloadManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DownloadController extends AbstractController
{
    private $_youtube;

    private $_youtubeHelper;

    public function __construct(
        YoutubeAPI $ytApi
    )
    {
        $this->_youtube = $ytApi->getClient();
        $this->_youtubeHelper = $ytApi;
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
            $downloadManager->addPlaylistToDownloadQueue($videos, Queue::ADDED_BY_CHANNEL_SUBSCRIBE);
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

        $downloadManager->addPlaylistToDownloadQueue($videos, Queue::ADDED_BY_DOWNLOAD_PLAYLIST);

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
        $downloadManager->addToDownloadQueue($vid, Queue::ADDED_BY_DOWNLOAD_SINGLE_SONG);

        return new RedirectResponse($this->generateUrl('home'));
    }
}
