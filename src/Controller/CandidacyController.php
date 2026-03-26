<?php

namespace App\Controller;

use App\Entity\Candidacy;
use App\Form\CandidacyType;
use App\Service\FileUpload;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CandidacyController extends AbstractController
{
    #[Route('/candidacies', name: 'candidacies')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $candidacies = $entityManager->getRepository(Candidacy::class)->findAll();

        return $this->render('candidacy/list.html.twig', [
            'candidacies' => $candidacies,
        ]);
    }

    #[Route('/add-candidacy', name: 'add_candidacy')]
    #[IsGranted("ROLE_USER")]
    public function addCandidacy(
        Request $request,
        EntityManagerInterface $entityManager,
        WorkflowInterface $candidacyReviewStateMachine,
        FileUpload $fileUpload
    ): Response
    {
        $candidacy = new Candidacy();
        $form = $this->createForm(CandidacyType::class, $candidacy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            $newFilename = $fileUpload->upload($uploadedFile);

            $candidacy->setFile($newFilename);

            $user = $this->getUser();

            $candidacy->setUser($user);

            $candidacyReviewStateMachine->getMarking($candidacy);

            $entityManager->persist($candidacy);
            $entityManager->flush();

            return $this->redirectToRoute('candidacies');
        }

        return $this->render('candidacy/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit-candidacy/{id}', name: 'edit_candidacy')]
    #[IsGranted("ROLE_USER")]
    public function editCandidacy(Request $request, EntityManagerInterface $entityManager, Candidacy $candidacy): Response
    {
        $form = $this->createForm(CandidacyType::class, $candidacy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidacy);
            $entityManager->flush();

            return $this->redirectToRoute('candidacies');
        }

        return $this->render('candidacy/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/deleteCandidacy/{id}', name: 'delete_candidacy')]
    #[IsGranted("ROLE_ADMIN")]
    public function deleteCandidacy(Request $request, EntityManagerInterface $entityManager, Candidacy $candidacy): Response
    {
        $entityManager->remove($candidacy);
        $entityManager->flush();

        return $this->redirectToRoute('candidacies');
    }

    #[Route('/candidacy/{id}', name: 'single_candidacy')]
    public function singleCandidacy(EntityManagerInterface $entityManager, Candidacy $candidacy, WorkflowInterface $candidacyReviewStateMachine
    ): Response
    {

        try {
            $candidacyReviewStateMachine->apply($candidacy, 'to_review');
            $entityManager->flush();
        } catch (LogicException $e) {
            throw $e;
        }

        return $this->render('candidacy/single.html.twig', [
            'candidacy' => $candidacy,
        ]);
    }
}
