<?php

namespace App\Presentation\HTTP\User;

use App\Application\Service\UserService;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\Voter\UserVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class GetUserAction
{
    public function __construct(
        private UserService $userService,
        private UserRepositoryInterface $userRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/users/{id}', name: 'api_v1_users_get', methods: ['GET'])]
    public function __invoke(string $id): Response
    {
        $user = $this->userRepository->findById($id);

        if (null === $user) {
            throw new NotFoundException('Resource not found');
        }

        if (!$this->authorizationChecker->isGranted(UserVoter::VIEW, $user)) {
            throw new AccessDeniedException();
        }

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($this->userService->findById($id), 'json'),
            Response::HTTP_OK
        );
    }
}
