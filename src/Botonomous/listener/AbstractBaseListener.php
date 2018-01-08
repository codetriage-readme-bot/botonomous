<?php

namespace Botonomous\listener;

use Botonomous\BotonomousException;
use Botonomous\Config;
use Botonomous\Event;
use Botonomous\utility\RequestUtility;

abstract class AbstractBaseListener
{
    const ORIGIN_VERIFICATION_SUCCESS_KEY = 'success';
    const ORIGIN_VERIFICATION_MESSAGE_KEY = 'message';

    private $config;
    private $request;
    private $requestUtility;

    /**
     * listen.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function listen()
    {
        // This is needed otherwise timeout error is displayed
        $this->respondOK();

        $request = $this->extractRequest();

        if (empty($request)) {
            /* @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        $this->setRequest($request);

        if ($this->isThisBot() !== false) {
            /* @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        return $request;
    }

    /**
     * @return mixed
     */
    abstract public function extractRequest();

    /**
     * @return string
     */
    abstract public function getChannelId(): string;

    /**
     * @return string
     */
    abstract public function getKey(): string;

    /**
     * @param null|string $key
     *
     * @return mixed
     */
    public function getRequest(string $key = null)
    {
        if (!isset($this->request)) {
            // each listener has its own way of extracting the request
            $this->setRequest($this->extractRequest());
        }

        if ($key === null) {
            // return the entire request since key is null
            return $this->request;
        }

        if (is_array($this->request) && array_key_exists($key, $this->request)) {
            return $this->request[$key];
        }
    }

    /**
     * @param array $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        if (!isset($this->config)) {
            $this->setConfig(new Config());
        }

        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Verify the request comes from Slack
     * Each listener must have have this and has got its own way to check the request.
     *
     * @throws \Exception
     *
     * @return array
     */
    abstract public function verifyOrigin();

    /**
     * Check if the request belongs to the bot itself.
     *
     * @throws \Exception
     *
     * @return bool
     */
    abstract public function isThisBot(): bool;

    /**
     * @return RequestUtility
     */
    public function getRequestUtility(): RequestUtility
    {
        if (!isset($this->requestUtility)) {
            $this->setRequestUtility((new RequestUtility()));
        }

        return $this->requestUtility;
    }

    /**
     * @param RequestUtility $requestUtility
     */
    public function setRequestUtility(RequestUtility $requestUtility)
    {
        $this->requestUtility = $requestUtility;
    }

    /**
     * respondOK.
     */
    protected function respondOK()
    {
        // check if fastcgi_finish_request is callable
        if (is_callable('fastcgi_finish_request')) {
            /*
             * http://stackoverflow.com/a/38918192
             * This works in Nginx but the next approach not
             */
            session_write_close();
            fastcgi_finish_request();

            /* @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        ignore_user_abort(true);

        ob_start();
        header($this->getRequestUtility()->getServerProtocol().' 200 OK');
        // Disable compression (in case content length is compressed).
        header('Content-Encoding: none');
        header('Content-Length: '.ob_get_length());

        // Close the connection.
        header('Connection: close');

        ob_end_flush();
        // only if an output buffer is active do ob_flush
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();

        /* @noinspection PhpInconsistentReturnPointsInspection */
    }

    /**
     * @throws \Exception
     *
     * @return array<string,boolean|string>
     */
    public function verifyRequest(): array
    {
        $originCheck = $this->verifyOrigin();

        if (!isset($originCheck[self::ORIGIN_VERIFICATION_SUCCESS_KEY])) {
            throw new BotonomousException('Success must be provided in verifyOrigin response');
        }

        if ($originCheck[self::ORIGIN_VERIFICATION_SUCCESS_KEY] !== true) {
            return [
                self::ORIGIN_VERIFICATION_SUCCESS_KEY => false,
                self::ORIGIN_VERIFICATION_MESSAGE_KEY => $originCheck[self::ORIGIN_VERIFICATION_MESSAGE_KEY],
            ];
        }

        if ($this->isThisBot() !== false) {
            return [
                self::ORIGIN_VERIFICATION_SUCCESS_KEY => false,
                self::ORIGIN_VERIFICATION_MESSAGE_KEY => 'Request comes from the bot',
            ];
        }

        return [self::ORIGIN_VERIFICATION_SUCCESS_KEY => true, self::ORIGIN_VERIFICATION_MESSAGE_KEY => 'Yay!'];
    }

    /**
     * @return string|null
     */
    public function determineAction()
    {
        $utility = $this->getRequestUtility();
        $getRequest = $utility->getGet();

        if (!empty($getRequest['action'])) {
            return strtolower($getRequest['action']);
        }

        $request = $utility->getPostedBody();

        if (isset($request['type']) && $request['type'] === 'url_verification') {
            return 'url_verification';
        }
    }

    /**
     * Return message based on the listener
     * If listener is event and event text is empty, fall back to request text.
     *
     * @throws \Exception
     *
     * @return mixed|string
     */
    public function getMessage()
    {
        if ($this instanceof EventListener && $this->getEvent() instanceof Event) {
            $message = $this->getEvent()->getText();

            if (!empty($message)) {
                return $message;
            }
        }

        return $this->getRequest('text');
    }
}
