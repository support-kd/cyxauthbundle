<?php
/**
 * Created by PhpStorm.
 * User: cyx-sanjay
 * Date: 19/12/2016
 * Time: 9:59 AM
 */

namespace KolossusD\CyxAuthBundle\Controller;


use Swift_Mailer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class CustomExceptionController extends Controller
{
    protected $twig;
    protected $debug;
    protected $exception_email;
    protected $exception_email_from;
    public function __construct(\Twig_Environment $twig, $debug, $exception_email, $exception_email_from)
    {
        $this->twig = $twig;
        $this->debug = $debug;
        $this->exception_email = $exception_email;
        $this->exception_email_from = $exception_email_from;
    }
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $showException = $request->attributes->get('showException', $this->debug); // As opposed to an additional parameter, this maintains BC

        $code = $exception->getStatusCode();
        $msg = $this->sendMail($exception);
        if($code == 500)
        $this->get('mailer')->send($msg);
        return new Response($this->twig->render(
            (string) $this->findTemplate($request, $request->getRequestFormat(), $code, $showException),
            array(
                'status_code' => $code,
                'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception' => 'sdfs',
                'logger' => $logger,
                'currentContent' => $currentContent,
            )
        ));
    }
    public function sendMail($exception)
    {

        $data = array(
            'to' => $this->exception_email,
            'from' => $this->exception_email_from,
            'subject'    => 'Error found on server',
            'raw_message'    => $exception
        );
        return $message = \Swift_Message::newInstance()
            ->setContentType("text/html")
            ->setSubject($data['subject'])
            ->setFrom($data['from'])
            ->setTo($data['to'])
            ->setBody(
                $data['raw_message'],
                'text/html'
            );
    }
    /**
     * @param int $startObLevel
     *
     * @return string
     */
    protected function getAndCleanOutputBuffering($startObLevel)
    {
        if (ob_get_level() <= $startObLevel) {
            return '';
        }

        Response::closeOutputBuffers($startObLevel + 1, true);

        return ob_get_clean();
    }

    /**
     * @param Request $request
     * @param string  $format
     * @param int     $code          An HTTP response status code
     * @param bool    $showException
     *
     * @return string
     */
    protected function findTemplate(Request $request, $format, $code, $showException)
    {
        $name = $showException ? 'exception' : 'error';
        if ($showException && 'html' == $format) {
            $name = 'exception_full';
        }

        // For error pages, try to find a template for the specific HTTP status code and format
        if (!$showException) {
            $template = sprintf('@Twig/Exception/%s%s.%s.twig', $name, $code, $format);
            if ($this->templateExists($template)) {
                return $template;
            }
        }

        // try to find a template for the given format
        $template = sprintf('@Twig/Exception/%s.%s.twig', $name, $format);
        if ($this->templateExists($template)) {
            return $template;
        }

        // default to a generic HTML exception
        $request->setRequestFormat('html');

        return sprintf('@Twig/Exception/%s.html.twig', $showException ? 'exception_full' : $name);
    }

    // to be removed when the minimum required version of Twig is >= 3.0
    protected function templateExists($template)
    {
        $template = (string) $template;

        $loader = $this->twig->getLoader();
        if ($loader instanceof \Twig_ExistsLoaderInterface) {
            return $loader->exists($template);
        }

        try {
            $loader->getSource($template);

            return true;
        } catch (\Twig_Error_Loader $e) {
        }

        return false;
    }
}