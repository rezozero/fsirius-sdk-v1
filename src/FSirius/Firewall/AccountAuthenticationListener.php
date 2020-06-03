<?php
declare(strict_types=1);

namespace RZ\FSirius\Firewall;

use Psr\Log\LoggerInterface;
use RZ\FSirius\Authentication\AccountToken;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

class AccountAuthenticationListener extends AbstractAuthenticationListener
{
    /**
     * AccountAuthenticationListener constructor.
     *
     * @param TokenStorageInterface                  $tokenStorage
     * @param AuthenticationManagerInterface         $authenticationManager
     * @param SessionAuthenticationStrategyInterface $sessionStrategy
     * @param HttpUtils                              $httpUtils
     * @param string                                 $providerKey
     * @param AuthenticationSuccessHandlerInterface  $successHandler
     * @param AuthenticationFailureHandlerInterface  $failureHandler
     * @param array                                  $options
     * @param LoggerInterface|null                   $logger
     * @param EventDispatcherInterface|null          $dispatcher
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        HttpUtils $httpUtils,
        string $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options = [],
        LoggerInterface $logger = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        parent::__construct($tokenStorage, $authenticationManager, $sessionStrategy, $httpUtils, $providerKey, $successHandler, $failureHandler, array_merge([
            'username_parameter' => '_email',
            'post_only' => true,
        ], $options), $logger, $dispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function requiresAuthentication(Request $request)
    {
        if ($this->options['post_only'] && !$request->isMethod('POST')) {
            return false;
        }

        return parent::requiresAuthentication($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        if ($this->options['post_only']) {
            $email = ParameterBagUtils::getParameterBagValue($request->request, $this->options['username_parameter']);
        } else {
            $email = ParameterBagUtils::getRequestParameterValue($request, $this->options['username_parameter']);
        }

        if (!\is_string($email) && (!\is_object($email) || !method_exists($email, '__toString'))) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['username_parameter'], \gettype($email)));
        }

        $email = trim((string) $email);

        if (\strlen((string) $email) > Security::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid email.');
        }

        $request->getSession()->set(Security::LAST_USERNAME, (string) $email);

        return $this->authenticationManager->authenticate(new AccountToken((string) $email, $this->providerKey));
    }
}
