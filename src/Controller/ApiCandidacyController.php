<?php

namespace App\Controller;

use App\Entity\Candidacy;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/candidacies', name: 'api_candidacies_')]
final class ApiCandidacyController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    #[IsGranted("ROLE_ADMIN")]
    #[OA\Response(
        response: 200,
        description: 'List of candidacies',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Candidacy::class, groups: ['candidacies:read']))
        )
    )]
    public function index(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $candidacies = $entityManager->getRepository(Candidacy::class)->findAll();
        $data = $serializer->serialize($candidacies, 'json', ['groups' => ['candidacies:read']]);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/add-candidacy/{id}', name: 'add_candidacy', methods: ['POST'])]
    #[IsGranted("ROLE_USER")]
    #[OA\Post]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                ]
            )
        )
    )]
    public function addCandidacy(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads')] string $uploadsDirectory,
        $id
    ): JsonResponse
    {
        $candidacy = new Candidacy();
        $uploadedFile = $request->files->get('file');

        $message = $request->request->get('message');
        $candidacy->setMessage($message);

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

        $offer = $entityManager->getRepository(Offer::class)->find($id);
        $candidacy->setOffer($offer);

        $entityManager->persist($candidacy);
        $entityManager->flush();

        $data = $serializer->serialize($candidacy, 'json', ['groups' => ['candidacies:read']]);

        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/edit-candidacy/{id}', name: 'edit_candidacy', methods: ['PUT'])]
    #[IsGranted("ROLE_USER")]
    #[OA\Put]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'file', type: 'string'),
                new OA\Property(property: 'offer', type: 'integer'),
            ]
        )
    )]
    public function editCandidacy(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, $id): JsonResponse
    {
        $candidacy = $entityManager->getRepository(Candidacy::class)->find($id);

        if (!$candidacy) {
            return new JsonResponse(['message' => 'Candidacy not found'], Response::HTTP_NOT_FOUND);
        }

        $updated_candidacy = $serializer->deserialize($request->getContent(), Candidacy::class, 'json', ['object_to_populate' => $candidacy]);

        $entityManager->persist($updated_candidacy);
        $entityManager->flush();

        $data = $serializer->serialize($updated_candidacy, 'json', ['groups' => ['candidacies:read']]);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/delete-candidacy/{id}', name: 'delete_candidacy', methods: ['DELETE'])]
    #[IsGranted("ROLE_USER")]
    #[OA\Response(
        response: 204,
        description: 'Delete candidacy',
    )]
    public function deleteCandidacy(EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $candidacy = $entityManager->getRepository(Candidacy::class)->find($id);

        if (!$candidacy) {
            return new JsonResponse(['message' => 'Candidacy not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($candidacy);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Candidacy deleted'], Response::HTTP_NO_CONTENT);
    }
}
