<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

readonly class RegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface        $mailer,
        private Environment            $twig,
    )
    {
    }

    /**
     * @param User $user
     * @return void
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function sendVerificationCode(User $user): void
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setVerificationCode($code);
        $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+15 minutes'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $email = (new Email())
            ->from('noreply@euroresil.fr')
            ->to($user->getEmail())
            ->subject('EURORESIL — Votre code de verification')
            ->html($this->twig->render('emails/verification_code.html.twig', ['code' => $code, 'user' => $user]));

        $this->mailer->send($email);
    }
}