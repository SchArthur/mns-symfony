<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\OfferType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class OfferController extends AbstractController
{
    #[Route('/add-offer', name: 'add_offer')]
    #[IsGranted("ROLE_ADMIN")]
    public function addOffer(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            $offer->setUser($user);

            $entityManager->persist($offer);
            $entityManager->flush();

            return $this->redirectToRoute('offers');
        }

        return $this->render('offer/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/offers', name: 'offers')]
    public function offers(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offers = $entityManager->getRepository(Offer::class)->findAll();

        return $this->render('offer/list.html.twig', [
            'offers' => $offers,
        ]);
    }

    #[Route('/edit-offer/{id}', name: 'edit_offer')]
    #[IsGranted("ROLE_ADMIN")]
    public function editOffer(Request $request, EntityManagerInterface $entityManager, Offer $offer): Response
    {
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            $offer->setUser($user);

            $entityManager->persist($offer);
            $entityManager->flush();

            return $this->redirectToRoute('offers');
        }

        return $this->render('offer/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/deleteOffer/{id}', name: 'delete_offer')]
    #[IsGranted("ROLE_ADMIN")]
    public function deleteOffer(Request $request, EntityManagerInterface $entityManager, Offer $offer): Response
    {
        $entityManager->remove($offer);
        $entityManager->flush();

        return $this->redirectToRoute('offers');
    }

    #[Route('/offer/{id}', name: 'single_offer')]
    public function singleOffer(Request $request, EntityManagerInterface $entityManager, Offer $offer): Response
    {
        return $this->render('offer/single.html.twig', [
            'offer' => $offer,
        ]);
    }
}
