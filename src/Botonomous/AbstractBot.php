<?php

namespace Botonomous;

use Botonomous\listener\AbstractBaseListener;
use Botonomous\utility\FormattingUtility;
use Botonomous\utility\LoggerUtility;
use Botonomous\utility\MessageUtility;
use Botonomous\utility\RequestUtility;

/**
 * Class AbstractBot.
 */
abstract class AbstractBot
{
    /**
     * Dependencies.
     */
    protected $config;
    protected $listener;
    protected $messageUtility;
    protected $commandContainer;
    protected $formattingUtility;
    protected $loggerUtility;
    protected $oauth;
    protected $blackList;
    protected $whiteList;
    protected $sender;
    protected $dictionary;
    protected $commandExtractor;

    /**
     * @param null $key
     *
     * @return mixed
     */
    abstract public function getRequest($key = null);

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        if ($this->config === null) {
            $this->config = (new Config());
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
     * @throws \Exception
     *
     * @return AbstractBaseListener
     */
    public function getListener(): AbstractBaseListener
    {
        if (!isset($this->listener)) {
            $listenerClass = __NAMESPACE__.'\\listener\\'.ucwords($this->getConfig()->get('listener')).'Listener';
            $this->setListener(new $listenerClass());
        }

        return $this->listener;
    }

    public function setListener(AbstractBaseListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * @return MessageUtility
     */
    public function getMessageUtility(): MessageUtility
    {
        if (!isset($this->messageUtility)) {
            $this->setMessageUtility(new MessageUtility());
        }

        return $this->messageUtility;
    }

    /**
     * @param MessageUtility $messageUtility
     */
    public function setMessageUtility(MessageUtility $messageUtility)
    {
        $this->messageUtility = $messageUtility;
    }

    /**
     * @return CommandContainer
     */
    public function getCommandContainer(): CommandContainer
    {
        if (!isset($this->commandContainer)) {
            $this->setCommandContainer(new CommandContainer());
        }

        return $this->commandContainer;
    }

    /**
     * @param CommandContainer $commandContainer
     */
    public function setCommandContainer(CommandContainer $commandContainer)
    {
        $this->commandContainer = $commandContainer;
    }

    /**
     * @return FormattingUtility
     */
    public function getFormattingUtility(): FormattingUtility
    {
        if (!isset($this->formattingUtility)) {
            $this->setFormattingUtility(new FormattingUtility());
        }

        return $this->formattingUtility;
    }

    /**
     * @param FormattingUtility $formattingUtility
     */
    public function setFormattingUtility(FormattingUtility $formattingUtility)
    {
        $this->formattingUtility = $formattingUtility;
    }

    /**
     * @return LoggerUtility
     */
    public function getLoggerUtility(): LoggerUtility
    {
        if (!isset($this->loggerUtility)) {
            $this->setLoggerUtility(new LoggerUtility());
        }

        return $this->loggerUtility;
    }

    /**
     * @param LoggerUtility $loggerUtility
     */
    public function setLoggerUtility(LoggerUtility $loggerUtility)
    {
        $this->loggerUtility = $loggerUtility;
    }

    /**
     * @return OAuth
     */
    public function getOauth(): OAuth
    {
        if (!isset($this->oauth)) {
            $this->setOauth(new OAuth());
        }

        return $this->oauth;
    }

    /**
     * @param OAuth $oauth
     */
    public function setOauth(OAuth $oauth)
    {
        $this->oauth = $oauth;
    }

    /**
     * @throws \Exception
     *
     * @return RequestUtility
     */
    public function getRequestUtility(): RequestUtility
    {
        return $this->getListener()->getRequestUtility();
    }

    /**
     * @param RequestUtility $requestUtility
     *
     * @throws \Exception
     */
    public function setRequestUtility(RequestUtility $requestUtility)
    {
        $this->getListener()->setRequestUtility($requestUtility);
    }

    /**
     * @throws \Exception
     *
     * @return BlackList
     */
    public function getBlackList(): BlackList
    {
        if (!isset($this->blackList)) {
            $this->setBlackList(new BlackList($this->getListener()->getRequest()));
        }

        return $this->blackList;
    }

    /**
     * @param BlackList $blackList
     */
    public function setBlackList(BlackList $blackList)
    {
        $this->blackList = $blackList;
    }

    /**
     * @throws \Exception
     *
     * @return WhiteList
     */
    public function getWhiteList(): WhiteList
    {
        if (!isset($this->whiteList)) {
            $this->setWhiteList(new WhiteList($this->getListener()->getRequest()));
        }

        return $this->whiteList;
    }

    /**
     * @param WhiteList $whiteList
     */
    public function setWhiteList(WhiteList $whiteList)
    {
        $this->whiteList = $whiteList;
    }

    /**
     * @return Sender
     */
    public function getSender(): Sender
    {
        if (!isset($this->sender)) {
            $this->setSender(new Sender($this));
        }

        return $this->sender;
    }

    /**
     * @param Sender $sender
     */
    public function setSender(Sender $sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return Dictionary
     */
    public function getDictionary(): Dictionary
    {
        if (!isset($this->dictionary)) {
            $this->setDictionary(new Dictionary());
        }

        return $this->dictionary;
    }

    /**
     * @param Dictionary $dictionary
     */
    public function setDictionary(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
    }

    /**
     * @return CommandExtractor
     */
    public function getCommandExtractor(): CommandExtractor
    {
        if (!isset($this->commandExtractor)) {
            $this->setCommandExtractor(new CommandExtractor());
        }

        return $this->commandExtractor;
    }

    /**
     * @param CommandExtractor $commandExtractor
     */
    public function setCommandExtractor(CommandExtractor $commandExtractor)
    {
        $this->commandExtractor = $commandExtractor;
    }
}
