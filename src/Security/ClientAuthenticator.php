<?php

namespace App\Security;

use App\Repository\PawnTicketRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class ClientAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private PawnTicketRepository $pawnTicketRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $ticketNumber = trim($request->request->getString('ticket_number'));
        $fullName = trim($request->request->getString('full_name'));
        $nameParts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $surname = $nameParts[0] ?? '';
        $name = $nameParts[1] ?? '';
        $patronymic = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 2)) : '';

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $ticketNumber);

        if (empty($ticketNumber) || empty($surname) || empty($name)) {
            throw new CustomUserMessageAuthenticationException('Заполните номер билета и ФИО (минимум фамилия и имя)');
        }

        return new SelfValidatingPassport(
            new UserBadge($ticketNumber, function () use ($ticketNumber, $surname, $name, $patronymic) {
                $ticket = $this->pawnTicketRepository->findByTicketNumber($ticketNumber);

                if (!$ticket) {
                    throw new CustomUserMessageAuthenticationException('Залоговый билет с данным номером не найден');
                }

                $client = $ticket->getClient();

                if (
                    mb_strtolower($client->getSurname()) !== mb_strtolower($surname) ||
                    mb_strtolower($client->getName()) !== mb_strtolower($name)
                ) {
                    throw new CustomUserMessageAuthenticationException('ФИО не совпадает с данными билета');
                }

                if ($patronymic !== '' && mb_strtolower($client->getPatronymic() ?? '') !== mb_strtolower($patronymic)) {
                    throw new CustomUserMessageAuthenticationException('ФИО не совпадает с данными билета');
                }

                return $client;
            }),
            [new CsrfTokenBadge('client_authenticate', $request->request->getString('_csrf_token'))]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_tickets_list'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_client_auth');
    }
}
