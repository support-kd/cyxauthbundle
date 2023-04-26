<?php
/**
 * Created by Sanjay.
 * User: cyx-sanjay
 * Date: 2/01/2017
 * Time: 1:43 PM
 */

namespace KolossusD\CyxAuthBundle\Controller;


use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use KolossusD\CyxSendgridBundle\Util\SendGridUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use KolossusD\CyxPostMarkBundle\Model\PostMarkModel;

/**
 * @RouteResource("api/login", pluralize=false)
 */
class RestLoginController extends FOSRestController implements ClassResourceInterface
{
    public function postAction()
    {
        // handled by Lexik JWT Bundle
        throw new \DomainException('You should never see this');
    }
    public function resetAction($token, Request $request){
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.resetting.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }
        if ($request->get('user_token') == $user->getConfirmationToken()) {
            try {
                $user->setPlainPassword($request->get('password'));
                //$user->setConfirmationToken('');
                $userManager->updateUser($user);
                $value = array("code" => 200, "success" => true, "message" => "Password Updated successfully");
            } catch(\Exception $e){
                $value = array("code" => 401, "error" => true, "message" => "Unable to update, please try again.");
            }
            return new Response(json_encode($value));

        }

        //you can return result as JSON
        return new Response(json_encode(array('user_token' => $token)));
    }
    public function request(){
        //you can return result as JSON
        $value = array("code" => 200, "success" => true);
        return new Response(json_encode($value));
    }
    public function sendEmailAction(Request $request)
    {
        $username = $request->request->get('username');

        /** @var $user UserInterface */
        $user = $this->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);
        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        /* Dispatch init event */
        $event = new GetResponseNullableUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            //return $event->getResponse();
            $value = array("code" => 200, "success" => true, "data" => $event->getResponse());
            return new Response(json_encode($value));
        }

        $ttl = $this->container->getParameter('fos_user.resetting.token_ttl');

            $event = new GetResponseUserEvent($user, $request);
            $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_REQUEST, $event);

            if (null !== $event->getResponse()) {
                return $event->getResponse();
            }

            if (null === $user->getConfirmationToken()) {
                /** @var $tokenGenerator TokenGeneratorInterface */
                $tokenGenerator = $this->get('fos_user.util.token_generator');
                $user->setConfirmationToken($tokenGenerator->generateToken());
            }

            /* Dispatch confirm event */
            $event = new GetResponseUserEvent($user, $request);
            $dispatcher->dispatch(FOSUserEvents::RESETTING_SEND_EMAIL_CONFIRM, $event);

            if (null !== $event->getResponse()) {
                $value = array("code" => 401, "success" => true, "data" => $event->getResponse());
                return new Response(json_encode($value));
            }

        if($this->container->getParameter('mailing_api') == 'postmark'){
            //Postmark
            $postMark = new PostMarkModel($this->container);
            $toEmail = $user->getEmail();
            $templateId = $this->container->getParameter('postmark_resetting_template');
            $url = $this->generateUrl('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);

            $postmar_params = array(
                'name' => $user->getFirstName(),
                'user' => $user->getFirstName().' '.$user->getLastName(),
                'url' => $url
            );

            $responce = $postMark->sendEmailWithTemplate($toEmail,$templateId,$postmar_params);


        }elseif($this->container->getParameter('sendgrid_api') && $this->container->getParameter('mailing_api') == 'sendgrid'){
                $sendgrid = new SendGridUtil($this->container->getParameter('sendgrid_api'), $this->container->getParameter('sendgrid_status'));
                $url = $this->generateUrl('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
                $array = array(
                    'from' => $this->container->getParameter('sender_email'),
                    'to' => $user->getEmail(),
                    'subject' => 'Resetting password',
                    'body' => 'some text here',
                    'template_id' => $this->container->getParameter('sendgrid_resetting_template'),
                    'subsitute' => array(
                        'user' => $user->getFirstName(),
						'username' => $user->getUsername(),
                        'url' => urldecode($url)
                    )

                );
                $responce = $sendgrid->sendByTemplateId($array);
            }else{
                $this->get('fos_user.mailer')->sendResettingEmailMessage($user);
            }
            //$this->get('cyx_auth.restmailer')->sendResettingEmailMessage($user);
            //$this->get('fos_user.mailer')->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new \DateTime());
            $this->get('fos_user.user_manager')->updateUser($user);

            /* Dispatch completed event */
            $event = new GetResponseUserEvent($user, $request);
            $dispatcher->dispatch(FOSUserEvents::RESETTING_SEND_EMAIL_COMPLETED, $event);

            if (null !== $event->getResponse()) {
                $value = array("code" => 401, "success" => true, "data" => $event->getResponse());
                return new Response(json_encode($value));
            }
        $value = array("code" => 200, "success" => true, "message" => "Link to change password is send to your mail");
        return new Response(json_encode($value));

        //return new RedirectResponse($this->generateUrl('fos_user_resetting_check_email', array('username' => $username)));
    }
}