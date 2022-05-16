<?php

namespace App\Controller;

use App\Dto\SearchInput;
use App\Repository\ReadEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SearchController
{
    public function __construct(
        private ReadEventRepository $repository,
        private SerializerInterface  $serializer
    ) {
    }

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function searchCommits(Request $request, ValidatorInterface $validator): JsonResponse
    {
        /** @phpstan-ignore-next-line */
        $searchInput = $this->serializer->denormalize($request->query->all(), SearchInput::class);

        $errors = $validator->validate($searchInput);
        if (!count($errors)) {
            $responseCode = Response::HTTP_OK;
            $countByType = $this->repository->countByType($searchInput);
            $data = [
                'meta' => [
                    'totalEvents' => $this->repository->countAll($searchInput),
                    'totalPullRequests' => $countByType['pullRequest'] ?? 0,
                    'totalCommits' => $countByType['commit'] ?? 0,
                    'totalComments' => $countByType['comment'] ?? 0,
                ],
                'data' => [
                    'events' => $this->repository->getLatest($searchInput),
                    'stats' => $this->repository->statsByTypePerHour($searchInput)
                ]
            ];
        } else {
            $responseCode = Response::HTTP_BAD_REQUEST;

            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            $data = [
                'errors' => $errorMessages
            ];
        }

        return new JsonResponse($data, $responseCode);
    }
}
