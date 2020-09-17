<?php
namespace App\Controller;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use GuzzleHttp\Client;
class RegistrationController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager )
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $entityManager->getRepository('App:User');
    }

    /**
     * @Route("/register/page", name="user_registration", methods={"GET"})
     */
    public function registerAction(Request $request)
    {
        return $this->render('registration/index.html.twig');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/register", name="register", methods={"POST"})
     */
    public function registerUsers(Request $request)
    {
        if ( $request->request->get('country_code') ) {

            $authy_api = new \Authy\AuthyApi( getenv('TWILIO_AUTHY_API_KEY') );
            $user      = $authy_api->registerUser( $request->request->get('email'), $request->request->get('phone_number'), $request->request->get('country_code') );

            if ( $user->ok() ) {

                $sms = $authy_api->requestSms( $user->id(), [ "force" => "true" ] );

                if ( $sms->ok() ) {

                    $this->addFlash(
                        'success',
                        $sms->message()
                    );
                }

                $user_params = [
                    'username' => $request->request->get('username'),
                    'email' => $request->request->get('email'),
                    'country_code' => $request->request->get('country_code'),
                    'phone_number' => $request->request->get('phone_number'),
                    'password' => $request->request->get('password'),
                    'authy_id' => $user->id(),
                ];

                $this->get('session')->set('user', $user_params);
            }
        }

        return $this->redirectToRoute('verify_page');

    }

    function updateDatabase($object)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();
    }
}