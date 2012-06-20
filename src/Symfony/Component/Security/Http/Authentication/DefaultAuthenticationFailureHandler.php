<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Class with the default authentication failure handling logic.
 *
 * Can be optionally be extended from by the developer to alter the behaviour
 * while keeping the default behaviour.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class DefaultAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    /**
     * @var HttpKernel
     */
    private $httpKernel;

    /**
     * @var HttpUtils
     */
    protected $httpUtils;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor.
     *
     * @param HttpKernelInterface $httpKernel Kernel
     * @param HttpUtils           $httpUtils  HttpUtils
     * @param array               $options    Options for processing a successful authentication attempt.
     * @param LoggerInterface     $logger     Optional logger
     */
    public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, array $options, LoggerInterface $logger = null)
    {
        $this->httpKernel = $httpKernel;
        $this->httpUtils  = $httpUtils;
        $this->logger     = $logger;

        $this->options = array_merge(array(
            'failure_path'                   => null,
            'failure_forward'                => false,
            'login_path'                     => '/login',
        ), $options);
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if (null === $this->options['failure_path']) {
            $this->options['failure_path'] = $this->options['login_path'];
        }

        if ($this->options['failure_forward']) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Forwarding to %s', $this->options['failure_path']));
            }

            $subRequest = $this->httpUtils->createRequest($request, $this->options['failure_path']);
            $subRequest->attributes->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);

            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Redirecting to %s', $this->options['failure_path']));
        }

        $request->getSession()->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);

        return $this->httpUtils->createRedirectResponse($request, $this->options['failure_path']);
    }
}
