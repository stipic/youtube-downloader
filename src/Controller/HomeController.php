<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\Channel;
use App\Repository\SongRepository;
use App\Repository\QueueRepository;
use App\Repository\ChannelRepository;
use App\Service\Downloader\YoutubeAPI;
use App\Service\Message\DownloadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use App\Service\Downloader\DownloadManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Pagination\SongPaginationService;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    const MAX_SONG_RESULTS = 100;

    /**
     * @Route("/page/{pageNum}", name="song_library_home", requirements={"pageNum"="\d+"})
     */
    public function indexPaginate(
        SongPaginationService $songPaginationService,
        $pageNum
    )
    {
        return $this->_homeCommon($pageNum, $songPaginationService);
    }

    /**
     * @Route("/", name="home")
     */
    public function index(
        SongPaginationService $songPaginationService,
        $pageNum = 1
    )
    {
        return $this->_homeCommon($pageNum, $songPaginationService);
    }

    private function _homeCommon($pageNum, $songPaginationService)
    {
        $results = $songPaginationService->paginate(self::MAX_SONG_RESULTS, $pageNum);

        // dashboard.
        return $this->render('home/index.html.twig', [
            'songs' => $results,
            'totalSongs' => count($results),
            'currentPage' => $pageNum,
            'totalPages' => $songPaginationService->total($results),
            'lastPage' => $songPaginationService->lastPage($results),
        ]);
    }
}
