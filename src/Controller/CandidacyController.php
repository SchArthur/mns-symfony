<?php

namespace App\Controller;

use App\Entity\Candidacy;
use App\Form\CandidacyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        #[Autowire('%kernel.project_dir%/public/uploads')] string $uploadsDirectory,
        SluggerInterface $slugger
    ): Response
    {
        $candidacy = new Candidacy();
        $form = $this->createForm(CandidacyType::class, $candidacy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

            try {
                $uploadedFile->move($uploadsDirectory, $newFilename);
            } catch (FileException $e) {
                throw new FileException($e);
            }

            $candidacy->setFile($newFilename);

            $user = $this->getUser();

            $candidacy->setUser($user);

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
    public function editCandidacy(Request $request, EntityManagerInterface $entityManager, $id): Response
    {
        $candidacy = $entityManager->getRepository(Candidacy::class)->find($id);

        if (!$candidacy) {
            return $this->redirectToRoute('candidacies');
        }

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
    public function deleteCandidacy(Request $request, EntityManagerInterface $entityManager, $id): Response
    {
        $candidacy = $entityManager->getRepository(Candidacy::class)->find($id);

        if (!$candidacy) {
            return $this->redirectToRoute('candidacies');
        }

        $entityManager->remove($candidacy);
        $entityManager->flush();

        return $this->redirectToRoute('candidacies');
    }

    #[Route('/candidacy/{id}', name: 'single_candidacy')]
    public function singleCandidacy(EntityManagerInterface $entityManager, $id): Response
    {
        $candidacy = $entityManager->getRepository(Candidacy::class)->find($id);

        if (!$candidacy) {
            return $this->redirectToRoute('candidacies');
        }

        return $this->render('candidacy/single.html.twig', [
            'candidacy' => $candidacy,
        ]);
    }
}
