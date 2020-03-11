<?php

namespace App\Controller;

use App\Repository\QueueRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DownloadController extends AbstractController
{
    /**
     * @Route("/download", name="downloadCenter")
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

        $onHoldBefore = $queueRepo->findQueueNumberForUser($this->getUser()->getId());

        // dashboard.
        return $this->render('download/index.html.twig', [
            'onHold' => count($onHold),
            'finished' => count($finished),
            'failed' => count($failed),
            'waitUntilFirst' => (int) $onHoldBefore['num']
        ]);
    }

    /**
     * @Route("/download/on-hold", name="onHold")
     */
    public function onHold(
        QueueRepository $queueRepo
    )
    {
        //@todo izračunati koliko je queue-ava ISPRED tvog prvog na redu.

        $onHold = $queueRepo->findBy([
            'user' => $this->getUser()->getId(),
            'finished' => 0,
            'status' => 0
        ]);

        // dashboard.
        return $this->render('download/on-hold.html.twig', [
            'onHoldCount' => count($onHold),
            'onHold' => $onHold
        ]);
    }
}
