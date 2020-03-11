<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Song;
use App\Repository\ChannelRepository;
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
    public function index()
    {
        // dashboard.
        return $this->render('home/index.html.twig', []);
    }

    /**
     * @Route("/channel/subscribe", name="channelSubscribe")
     */
    public function channelSubscribe(
        Request $request,
        EntityManagerInterface $em,
        ChannelRepository $channelRepository
    )
    {
        $channelUrl = $request->get('channel');
        $channel = $this->_youtube->getChannelFromURL($channelUrl);
        $user = $this->getUser();

        if(isset($channel->id))
        {
            $dbChannel = $channelRepository->findOneBy(['ytId' => $channel->id]);
            if(!$dbChannel instanceof Channel)
            {
                // Kanal ne postoj, kreiraj ga i attachaj usera.

                $xChannel = new Channel();
                $xChannel->setYtId($channel->id);
                $xChannel->setTitle($channel->snippet->title);
                $xChannel->addSubscriber($user);

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
        $playlist = $this->_youtube->getPlaylistItemsByPlaylistId($playlistId);
        foreach($playlist as $ytSong)
        {
            $url = 'https://www.youtube.com/watch?v=' . $ytSong->snippet->resourceId->videoId;
            $downloadManager->addToDownloadQueue($url, $ytSong); 
        }

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
