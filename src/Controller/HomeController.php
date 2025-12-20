<?php

namespace App\Controller;

use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/create-session', name: 'app_create_session', methods: ['POST'])]
    public function createSession(Request $request, EntityManagerInterface $em): Response
    {
        $sessionName = $request->request->get('session_name');
        $maxTracks = $request->request->get('max_tracks');

        if (empty($sessionName)) {
            $this->addFlash('error', 'Le nom de la session est requis.');
            return $this->redirectToRoute('app_home');
        }

        $session = new Session();
        $session->setName($sessionName);

        if (!empty($maxTracks) && $maxTracks > 0) {
            $session->setMaxTracksPerParticipant((int)$maxTracks);
        }

        $em->persist($session);
        $em->flush();

        return $this->redirectToRoute('app_join_session', ['code' => $session->getCode()]);
    }

    #[Route('/join-session', name: 'app_join_session_code', methods: ['POST'])]
    public function joinSessionByCode(Request $request, EntityManagerInterface $em): Response
    {
        $code = strtoupper($request->request->get('code'));

        if (empty($code)) {
            $this->addFlash('error', 'Le code de session est requis.');
            return $this->redirectToRoute('app_home');
        }

        $session = $em->getRepository(Session::class)->findOneBy(['code' => $code]);

        if (!$session) {
            $this->addFlash('error', 'Session introuvable.');
            return $this->redirectToRoute('app_home');
        }

        return $this->redirectToRoute('app_join_session', ['code' => $code]);
    }
}
