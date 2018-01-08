<?php

namespace Botonomous;

use PHPUnit\Framework\TestCase;

/**
 * Class CommandExtractorTest.
 */

/** @noinspection PhpUndefinedClassInspection */
class CommandExtractorTest extends TestCase
{
    const VERIFICATION_TOKEN = 'verificationToken';

    /**
     * @throws \Exception
     */
    public function testRespondWithoutDefaultCommand()
    {
        $config = new Config();
        $config->set('defaultCommand', '');

        $message = 'dummy dummy dummy dummy';

        /**
         * Form the request.
         */
        $request = [
            'token' => $config->get(self::VERIFICATION_TOKEN),
            'text'  => $message,
        ];

        $slackbot = new Slackbot();

        // get listener
        $listener = $slackbot->getListener();

        // set request
        $listener->setRequest($request);

        $this->assertEquals($this->outputOnNoCommand($message), $slackbot->respond());
    }

    /**
     * @param $message
     *
     * @throws \Exception
     *
     * @return mixed
     */
    private function outputOnNoCommand($message)
    {
        $config = new Config();
        $defaultCommand = $config->get('defaultCommand');

        $token = $config->get(self::VERIFICATION_TOKEN);

        $slackbot = new Slackbot();

        // get listener
        $listener = $slackbot->getListener();

        // set request
        $listener->setRequest(['text' => $message, 'token' => $token]);

        if (!empty($defaultCommand)) {
            $commandObject = (new CommandContainer())->getAsObject($defaultCommand);
            /** @noinspection PhpUndefinedMethodInspection */
            $commandClass = $commandObject->getClass();

            /**
             * @var AbstractPlugin
             */
            $pluginObject = (new $commandClass($slackbot));

            /* @noinspection PhpUndefinedMethodInspection */
            return $pluginObject->index();
        }

        return (new Dictionary())->get('generic-messages')['noCommandMessage'];
    }

    /**
     * @throws \Exception
     */
    public function testRespondExceptException()
    {
        $config = new Config();
        $commandPrefix = $config->get('commandPrefix');

        /**
         * Form the request.
         */
        $botUserId = '<@'.$config->get('botUserId').'>';
        $request = [
            'token' => $config->get(self::VERIFICATION_TOKEN),
            'text'  => "{$botUserId} {$commandPrefix}commandWithoutFunctionForTest",
        ];

        $this->expectException('\Exception');
        $this->expectExceptionMessage(
            'Action / function: \'commandWithoutFunctionForTest\' does not exist in \'Botonomous\plugin\ping\Ping\''
        );

        $slackbot = new Slackbot();

        // get listener
        $listener = $slackbot->getListener();

        // set request
        $listener->setRequest($request);

        $response = $slackbot->respond();

        // @codeCoverageIgnoreStart
        $this->assertEquals('', $response);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws \Exception
     */
    public function testGetCommandByMessage()
    {
        /**
         * Form the request.
         */
        $config = new Config();
        $commandPrefix = $config->get('commandPrefix');
        $request = [
            'token' => $config->get(self::VERIFICATION_TOKEN),
        ];

        $slackbot = new Slackbot();

        // get listener
        $listener = $slackbot->getListener();

        // set request
        $listener->setRequest($request);

        $result = (new CommandExtractor())->getCommandByMessage("{$commandPrefix}ping message");

        $this->assertEquals('index', $result->getAction());
        $this->assertEquals('Ping', $result->getPlugin());
    }

    /**
     * @throws \Exception
     */
    public function testGetCommandByMessageWithoutDefaultCommand()
    {
        $config = new Config();

        /**
         * Form the request.
         */
        $request = [
            'token' => $config->get(self::VERIFICATION_TOKEN),
        ];

        $config->set('defaultCommand', '');

        $slackbot = new Slackbot();

        // get listener
        $listener = $slackbot->getListener();

        // set request
        $listener->setRequest($request);

        $commandExtractor = new CommandExtractor();
        $commandExtractor->getCommandByMessage('dummy message without command');

        $this->assertEquals(
            (new Dictionary())->get('generic-messages')['noCommandMessage'],
            $commandExtractor->getError()
        );
    }

    /**
     * Test getCommandByMessage.
     */
    public function testGetCommandByMessageEmptyMessage()
    {
        $config = new Config();

        /**
         * Form the request.
         */
        $request = [
            'token' => $config->get(self::VERIFICATION_TOKEN),
            'text'  => '',
        ];

        $slackbot = new Slackbot();

        // get listener
        $listener = $slackbot->getListener();

        // set request
        $listener->setRequest($request);

        $commandExtractor = new CommandExtractor();
        $result = $commandExtractor->getCommandByMessage('');

        $this->assertEquals('Message is empty', $commandExtractor->getError());

        $this->assertEmpty($result);
    }

    /**
     * Test countKeywordOccurrence.
     */
    public function testCountKeywordOccurrence()
    {
        $commandExtractor = new CommandExtractor();

        $commandContainer = new CommandContainer();
        $originalCommands = $commandContainer->getAll();

        $commands = [
            'dummy1' => [
                CommandContainer::PLUGIN_KEY      => 'Ping',
                CommandContainer::DESCRIPTION_KEY => 'Use as a health check',
                CommandContainer::KEYWORDS_KEY    => [
                    'play',
                    'sport',
                    'pong',
                ],
            ],
            'dummy2' => [
                CommandContainer::PLUGIN_KEY      => 'Ping',
                CommandContainer::ACTION_KEY      => 'pong',
                CommandContainer::DESCRIPTION_KEY => 'Use as a health check',
                CommandContainer::KEYWORDS_KEY    => [
                    'play',
                    'sport',
                    'ping',
                ],
            ],
            'dummy3' => [
                CommandContainer::PLUGIN_KEY      => 'Ping',
                CommandContainer::ACTION_KEY      => 'pong',
                CommandContainer::DESCRIPTION_KEY => 'Use as a health check',
            ],
            'dummy4' => [
                CommandContainer::PLUGIN_KEY      => 'Ping',
                CommandContainer::ACTION_KEY      => 'pong',
                CommandContainer::DESCRIPTION_KEY => 'Use as a health check',
                CommandContainer::KEYWORDS_KEY    => [
                    'play ping pong',
                    'play',
                    "let's play",
                ],
            ],
        ];

        $commandContainer->setAll($commands);

        $commandExtractor->setCommandContainer($commandContainer);

        $keywordsCount = $commandExtractor->countKeywordOccurrence("let's play ping pong");

        $expected = [
            'dummy1' => 2,
            'dummy2' => 2,
            'dummy4' => 1,
            'dummy3' => 0,
        ];

        $commandContainer->setAll($originalCommands);

        $this->assertEquals($expected, $keywordsCount);
    }

    /**
     * Test getCommandWithKeywordByMessage.
     */
    public function testGetCommandWithKeywordByMessage()
    {
        $commandExtractor = new CommandExtractor();

        $commandContainer = new CommandContainer();
        $originalCommands = $commandContainer->getAll();

        $commandsArray = [
            'weather' => [
                'plugin'      => 'Ping',
                'description' => 'Use as a health check',
                'keywords'    => [
                    'weather',
                    'forecast',
                ],
            ],
            'report' => [
                'plugin'      => 'Ping',
                'action'      => 'pong',
                'description' => 'Use as a health check',
                'keywords'    => [
                    'visitors',
                    'forecast',
                ],
            ],
            'dummy' => [
                'plugin'      => 'Ping',
                'action'      => 'pong',
                'description' => 'Use as a health check',
            ],
        ];

        $commandContainer->setAll($commandsArray);

        $commandExtractor->setCommandContainer($commandContainer);

        $commandObjects = [];
        foreach ($commandsArray as $commandKey => $commandDetails) {
            $command = new Command($commandKey);
            $command->load($commandDetails);
            $commandObjects[$commandKey] = $command;
        }

        $commandObject = $commandExtractor->getCommandByMessage("What's the weather like?");
        $this->assertEquals($commandObjects['weather'], $commandObject);

        $command = $commandExtractor->getCommandByMessage('Can you forecast the visitors?');
        $this->assertEquals($commandObjects['report'], $command);

        $config = new Config();
        $commandPrefix = $config->get('commandPrefix');
        $command = $commandExtractor->getCommandByMessage("{$commandPrefix}dummy Can you forecast the visitors?");
        $this->assertEquals($commandObjects['dummy'], $command);

        $command = $commandExtractor->getCommandByMessage("{$commandPrefix}weather Can you forecast the visitors?");
        $this->assertEquals($commandObjects['weather'], $command);

        $defaultCommand = $config->get('defaultCommand');
        $expected = isset($commandObjects[$defaultCommand]) ? $commandObjects[$defaultCommand] : null;

        // since there is no command, it tries to get the default command but it also depends to $commandsArray
        $command = $commandExtractor->getCommandByMessage('there is no command in this message and no keywords');
        $this->assertEquals($expected, $command);

        $commandContainer->setAll($originalCommands);
    }

    /**
     * Test getConfig.
     */
    public function testGetConfig()
    {
        $commandExtractor = new CommandExtractor();
        $config = new Config();
        $commandExtractor->setConfig($config);

        $this->assertEquals($config, $commandExtractor->getConfig());
    }
}
