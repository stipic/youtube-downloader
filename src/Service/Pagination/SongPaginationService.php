<?php

namespace App\Service\Pagination;

use App\Repository\SongRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SongPaginationService
{
    private $_songRepo;

    private $_tokenStorage;

    public function __construct(
        SongRepository $songRepository,
        TokenStorageInterface $tokenStorageInterface
    )
    {
        $this->_songRepo = $songRepository;
        $this->_tokenStorage = $tokenStorageInterface;
    }

    public function paginate($query, int $limit, int $currentPage): Paginator
    {
        $query = $this->_songRepo->findUserSongs($query, $this->_tokenStorage->getToken()->getUser());
        
        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);
        
        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($currentPage - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    public function lastPage(Paginator $paginator): int
    {
        return ceil($paginator->count() / $paginator->getQuery()->getMaxResults());
    }

    public function total(Paginator $paginator): int
    {
        return $paginator->count();
    }

    public function currentPageHasNoResult(Paginator $paginator): bool
    {
        return !$paginator->getIterator()->count();
    }
}
