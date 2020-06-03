<?php

namespace App\EventListener;

use App\Routing\Matcher\RequestMatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

class RouterListener implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpKernel\EventListener\RouterListener
     */
    private $routerListener;

    /**
     * @var RequestContext
     */
    private $context;

    /**
     * @var RequestMatcher
     */
    private $requestMatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \Symfony\Component\HttpKernel\EventListener\RouterListener $routerListener,
        RequestContext $context,
        RequestMatcher $requestMatcher,
        LoggerInterface $logger
    ) {
        $this->routerListener = $routerListener;
        $this->requestMatcher = $requestMatcher;
        $this->logger = $logger;
        $this->context = $context;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 64]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
            KernelEvents::EXCEPTION => ['onKernelException', -64],
        ];
    }

    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        return $this->routerListener->onKernelFinishRequest($event);
    }

    public function onKernelException(ExceptionEvent $event)
    {
        return $this->routerListener->onKernelException($event);
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        $this->setCurrentRequest($request);

        if ($request->attributes->has('_controller')) {
            // routing is already done
            return;
        }

        // add attributes based on the request (routing)
        try {
            $parameters = $this->requestMatcher->matchRequest($request);
//            dd([
//                'route' => isset($parameters['_route']) ? $parameters['_route'] : 'n/a',
//                'route_parameters' => $parameters,
//                'request_uri' => $request->getUri(),
//                'method' => $request->getMethod(),
//            ]);
            $this->logger->info('Matched route "{route}".', [
                'route' => isset($parameters['_route']) ? $parameters['_route'] : 'n/a',
                'route_parameters' => $parameters,
                'request_uri' => $request->getUri(),
                'method' => $request->getMethod(),
            ]);

            $request->setLocale($parameters['locale']);
            $request->attributes->add($parameters);
            unset($parameters['_route'], $parameters['_controller']);
            $request->attributes->set('_route_params', $parameters);
        } catch (ResourceNotFoundException $e) {
            $message = sprintf('No route found for "%s %s"', $request->getMethod(), $request->getPathInfo());

            if ($referer = $request->headers->get('referer')) {
                $message .= sprintf(' (from "%s")', $referer);
            }

            throw new NotFoundHttpException($message, $e);
        } catch (MethodNotAllowedException $e) {
            $message = sprintf('No route found for "%s %s": Method Not Allowed (Allow: %s)', $request->getMethod(), $request->getPathInfo(), implode(', ', $e->getAllowedMethods()));

            throw new MethodNotAllowedHttpException($e->getAllowedMethods(), $message, $e);
        }
    }

    private function setCurrentRequest(Request $request = null)
    {
        if (null !== $request) {
            try {
                $this->context->fromRequest($request);
            } catch (\UnexpectedValueException $e) {
                throw new BadRequestHttpException($e->getMessage(), $e, $e->getCode());
            }
        }
    }
}
