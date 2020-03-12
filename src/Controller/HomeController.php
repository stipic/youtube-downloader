<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Pagination\SongPaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    const MAX_SONG_RESULTS = 100;

    /**
     * @Route("/page/{pageNum}", name="song_library_home", requirements={"pageNum"="\d+"})
     */
    public function indexPaginate(
        Request $request,
        SongPaginationService $songPaginationService,
        $pageNum
    )
    {
        $sQuery = $request->get('q');
        return $this->_homeCommon($pageNum, $songPaginationService, $sQuery);
    }

    /**
     * @Route("/", name="home")
     */
    public function index(
        Request $request,
        SongPaginationService $songPaginationService,
        $pageNum = 1
    )
    {
        $sQuery = $request->get('q');
        return $this->_homeCommon($pageNum, $songPaginationService, $sQuery);
    }

    private function _homeCommon($pageNum, $songPaginationService, $sQuery)
    {
        $results = $songPaginationService->paginate($sQuery, self::MAX_SONG_RESULTS, $pageNum);

        // dashboard.
        return $this->render('home/index.html.twig', [
            'searchQuery' => $sQuery,
            'songs' => $results,
            'totalSongs' => count($results),
            'currentPage' => $pageNum,
            'totalPages' => $songPaginationService->total($results),
            'lastPage' => $songPaginationService->lastPage($results),
        ]);
    }
}
