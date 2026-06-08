<?php

namespace App\Presentation\HTTP\User;

use App\Application\Service\UserService;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\Voter\UserVoter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsController]
final readonly class DeleteUserAction
{
    public function __construct(
        private UserService $userService,
        private UserRepositoryInterface $userRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[Route('/api/v1/users/{id}', name: 'api_v1_users_delete', methods: ['DELETE'])]
    public function __invoke(string $id): Response
    {
        $user = $this->userRepository->findById($id);

        if (null === $user) {
            throw new NotFoundException('Resource not found');
        }

        if (!$this->authorizationChecker->isGranted(UserVoter::DELETE, $user)) {
            throw new AccessDeniedException();
        }

        $this->userService->delete($user);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
