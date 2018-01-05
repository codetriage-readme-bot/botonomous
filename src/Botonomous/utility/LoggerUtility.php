<?php

namespace Botonomous\utility;

use Botonomous\BotonomousException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerUtility.
 */
class LoggerUtility extends AbstractUtility
{
    const DATE_FORMAT = 'Y-m-d H:i:s';
    const TEMP_FOLDER = 'tmp';

    const DEBUG = 'debug';
    const INFO = 'info';
    const NOTICE = 'notice';
    const WARNING = 'warning';
    const ERROR = 'error';
    const CRITICAL = 'critical';
    const ALERT = 'alert';
    const EMERGENCY = 'emergency';

    const HANDLERS_KEY = 'handlers';

    public static $levels = [
        self::DEBUG,
        self::INFO,
        self::NOTICE,
        self::WARNING,
        self::ERROR,
        self::CRITICAL,
        self::ALERT,
        self::EMERGENCY,
    ];

    private $logger;

    /**
     * LoggerUtility constructor.
     *
     * @param null $config
     *
     * @throws \Exception
     */
    public function __construct($config = null)
    {
        parent::__construct($config);

        try {
            $this->initLogger();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Init the logger.
     *
     * @throws \Exception
     */
    private function initLogger()
    {
        $monologConfig = $this->getMonologConfig();

        if (empty($monologConfig)) {
            throw new BotonomousException('Monolog config is missing');
        }

        $logger = new Logger($monologConfig['channel']);

        foreach (array_keys($monologConfig[self::HANDLERS_KEY]) as $value) {
            $logger = $this->pushMonologHandler($logger, $value);
        }

        $this->setLogger($logger);
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    private function getMonologConfig()
    {
        $loggerConfig = $this->getConfig()->get('logger');

        return !empty($loggerConfig['monolog']) ? $loggerConfig['monolog'] : false;
    }

    /**
     * @param Logger $logger
     * @param string $handlerKey
     *
     * @throws \Exception
     *
     * @return Logger
     */
    private function pushMonologHandler(Logger $logger, string $handlerKey): Logger
    {
        $activeHandlers = [];

        // if there are more $handlerKey, use switch
        if ($handlerKey === 'file') {
            $activeHandlers[] = new StreamHandler($this->getLogFilePath());
        }

        if (!empty($activeHandlers)) {
            foreach ($activeHandlers as $activeHandler) {
                $logger->pushHandler($activeHandler);
            }
        }

        return $logger;
    }

    /**
     * @throws \Exception
     *
     * @return false|string
     */
    public function getLogFilePath()
    {
        $monologConfigFile = $this->getMonologConfigFileName();
        if (empty($monologConfigFile)) {
            return false;
        }

        return $this->getTempDir().DIRECTORY_SEPARATOR.$monologConfigFile;
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    private function getMonologConfigFileName()
    {
        $monologConfig = $this->getMonologConfig();

        if (isset($monologConfig[self::HANDLERS_KEY]['file']['fileName'])) {
            return $monologConfig[self::HANDLERS_KEY]['file']['fileName'];
        }
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    private function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $function
     * @param string $message
     * @param string $channel
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logChat(string $function, string $message = '', string $channel = ''): bool
    {
        try {
            return $this->logInfo('Log Chat', [
                'function' => $function,
                'message'  => $message,
                'channel'  => $channel,
            ]);
        } catch (\Exception $e) {
            throw new BotonomousException($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     *
     * @return bool
     */
    private function canLog(): bool
    {
        $loggerConfig = $this->getConfig()->get('logger');

        return empty($loggerConfig['enabled']) ? false : true;
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    public function getTempDir(): string
    {
        return dirname(__DIR__).DIRECTORY_SEPARATOR.self::TEMP_FOLDER;
    }

    /**
     * @param $function
     * @param string $message
     * @param $channel
     *
     * @return string
     */
    public function getLogContent(string $function, string $message, string $channel): string
    {
        return "{$function}|{$message}|{$channel}";
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logDebug(string $message, array $context = []): bool
    {
        return $this->log(self::DEBUG, $message, $context);
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logInfo(string $message, array $context = []): bool
    {
        return $this->log(self::INFO, $message, $context);
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logNotice(string $message, array $context = []): bool
    {
        return $this->log(self::NOTICE, $message, $context);
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logWarning(string $message, array $context = []): bool
    {
        return $this->log(self::WARNING, $message, $context);
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logError(string $message, array $context = []): bool
    {
        return $this->log(self::ERROR, $message, $context);
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logCritical(string $message, array $context = []): bool
    {
        return $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logAlert(string $message, array $context = []): bool
    {
        return $this->log(self::ALERT, $message, $context);
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function logEmergency(string $message, array $context = []): bool
    {
        return $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * @param string $level
     * @param $message
     * @param array $context
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function log(string $level, string $message, array $context = []): bool
    {
        if ($this->canLog() !== true) {
            return false;
        }

        $logger = $this->getLogger();

        if (!in_array($level, self::$levels)) {
            throw new BotonomousException("'{$level}' is an invalid log level");
        }

        $logger->$level($message, $context);

        return true;
    }
}
