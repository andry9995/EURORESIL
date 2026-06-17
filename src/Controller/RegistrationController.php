<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationParticulierType;
use App\Form\RegistrationProType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth')]
class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register_choice')]
    public function choice(): Response
    {
        return $this->render('registration/choice.html.twig');
    }

    #[Route('/register/particulier', name: 'app_register_particulier')]
    public function registerParticulier(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        $user = new User();
        $user->setProfile('particulier');
        $form = $this->createForm(RegistrationParticulierType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $this->sendVerificationCode($user, $em, $mailer);
            $request->getSession()->set('pending_user_id', $user->getId());
            return $this->redirectToRoute('app_verify_email');
        }

        return $this->render('registration/particulier.html.twig', ['form' => $form]);
    }

    #[Route('/register/pro', name: 'app_register_pro')]
    public function registerPro(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        $user = new User();
        $user->setProfile('pro');
        $form = $this->createForm(RegistrationProType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $this->sendVerificationCode($user, $em, $mailer);
            $request->getSession()->set('pending_user_id', $user->getId());
            return $this->redirectToRoute('app_verify_email');
        }

        return $this->render('registration/pro.html.twig', ['form' => $form]);
    }

    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET','POST'])]
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
                $this->addFlash('error', 'Code expire. Demandez un nouveau code.');
            } else {
                $user->setEmailVerifiedAt(new \DateTimeImmutable());
                $user->setVerificationCode(null);
                $em->flush();
                $request->getSession()->remove('pending_user_id');
                $this->addFlash('success', 'Compte verifie ! Vous pouvez vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/verify_email.html.twig');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET','POST'])]
    public function forgotPassword(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user  = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $this->sendVerificationCode($user, $em, $mailer);
                $request->getSession()->set('reset_user_id', $user->getId());
            }
            // Toujours rediriger (ne pas divulguer si l'email existe)
            return $this->redirectToRoute('app_reset_password');
        }
        return $this->render('registration/forgot_password.html.twig');
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET','POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $userId = $request->getSession()->get('reset_user_id');
        if (!$userId) return $this->redirectToRoute('app_forgot_password');

        $user = $em->find(User::class, $userId);

        if ($request->isMethod('POST')) {
            $code     = $request->request->get('code');
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

    private function sendVerificationCode(User $user, EntityManagerInterface $em, MailerInterface $mailer): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setVerificationCode($code);
        $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+15 minutes'));
        $em->persist($user);
        $em->flush();

        $email = (new Email())
            ->from('noreply@euroresil.fr')
            ->to($user->getEmail())
            ->subject('EURORESIL — Votre code de verification')
            ->html($this->renderView('emails/verification_code.html.twig', ['code' => $code, 'user' => $user]));

        $mailer->send($email);
    }
}
