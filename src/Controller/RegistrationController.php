<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Profile;
use App\Form\RegistrationParticulierType;
use App\Form\RegistrationProType;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

#[Route('/auth')]
class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService
    )
    {
    }

    #[Route('/register', name: 'app_register_choice')]
    public function choice(): Response
    {
        return $this->render('registration/choice.html.twig');
    }

    /**
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @return Response
     * @throws TransportExceptionInterface
     */
    #[Route('/register/particulier', name: 'app_register_particulier')]
    public function registerParticulier(
        Request                     $request,
        UserPasswordHasherInterface $hasher,
    ): Response
    {
        $user = new User();
        $user->setProfile(Profile::PARTICULAR);
        $form = $this->createForm(RegistrationParticulierType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $this->registrationService->sendVerificationCode($user);
            $request->getSession()->set('pending_user_id', $user->getId());
            return $this->redirectToRoute('app_verify_email');
        }

        return $this->render('registration/particulier.html.twig', ['form' => $form]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register/pro', name: 'app_register_pro')]
    public function registerPro(
        Request                     $request,
        UserPasswordHasherInterface $hasher,
    ): Response
    {
        $user = new User();
        $user->setProfile(Profile::PRO);
        $form = $this->createForm(RegistrationProType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $this->registrationService->sendVerificationCode($user);
            $request->getSession()->set('pending_user_id', $user->getId());

            return $this->redirectToRoute('app_verify_email');
        }

        return $this->render('registration/pro.html.twig', ['form' => $form]);
    }

    /**
     * @throws ORMException
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws OptimisticLockException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET', 'POST'])]
    public function verifyEmail(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('pending_user_id');
        if (!$userId) return $this->redirectToRoute('app_register_choice');

        $user = $em->find(User::class, $userId);
        if (!$user) return $this->redirectToRoute('app_register_choice');

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');

            if ($user->getVerificationCode() !== $code) {
                $this->addFlash('error', 'Code incorrect. Veuillez reessayer.');
            } elseif ($user->getVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
                $this->addFlash('error', 'Le code a expiré. Demandez un nouveau code.');
            } else {
                $response = $this->registrationService->createLetRecoAccount($user);

                if(!$response['status']) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la vérification de votre compte');
                    return $this->render('registration/verify_email.html.twig');
                }

                $user->setEmailVerifiedAt(new \DateTimeImmutable());
                $user->setVerificationCode(null);
                $em->flush();
                $request->getSession()->remove('pending_user_id');
                $this->addFlash('success', 'Compte vérifié ! Vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/verify_email.html.twig');
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @throws TransportExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/resend-verification-email', name: 'app_resend_verification_email', methods: ['GET'])]
    public function resendVerificationEmail(
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        $userId = $request->getSession()->get('pending_user_id');
        if (!$userId) return $this->redirectToRoute('app_register_choice');

        $user = $em->find(User::class, $userId);
        if (!$user) return $this->redirectToRoute('app_register_choice');

        $this->registrationService->sendVerificationCode($user);
        $request->getSession()->set('pending_user_id', $user->getId());

        return $this->redirectToRoute('app_verify_email');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $this->registrationService->sendVerificationCode($user);
                $request->getSession()->set('reset_user_id', $user->getId());
            }
            // Toujours rediriger (ne pas divulguer si l'email existe)
            return $this->redirectToRoute('app_reset_password');
        }
        return $this->render('registration/forgot_password.html.twig');
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher,
    ): Response
    {
        $userId = $request->getSession()->get('reset_user_id');
        if (!$userId) return $this->redirectToRoute('app_forgot_password');

        $user = $em->find(User::class, $userId);

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');
            $password = $request->request->get('password');

            if ($user && $user->getVerificationCode() === $code && $user->getVerificationCodeExpiresAt() > new \DateTimeImmutable()) {
                $user->setPassword($hasher->hashPassword($user, $password));
                $user->setVerificationCode(null);
                $em->flush();
                $request->getSession()->remove('reset_user_id');
                $this->addFlash('success', 'Mot de passe reinitialise.');
                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('error', 'Code incorrect ou expire.');
        }

        return $this->render('registration/reset_password.html.twig');
    }
}
