<?php

namespace Botonomous\listener;

use Botonomous\BotonomousException;

class SlashCommandListener extends AbstractBaseListener
{
    const KEY = 'slashCommand';
    const VERIFICATION_TOKEN = 'verificationToken';
    const MISSING_TOKEN_MESSAGE = 'Token is missing';
    const MISSING_TOKEN_CONFIG_MESSAGE = 'Token must be set in the config';

    /**
     * @return mixed
     */
    public function extractRequest()
    {
        $postRequest = $this->getRequestUtility()->getPost();

        if (empty($postRequest)) {
            /* @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        return $postRequest;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function verifyOrigin(): array
    {
        $token = $this->getRequest('token');

        if (empty($token)) {
            return [
                parent::ORIGIN_VERIFICATION_SUCCESS_KEY => false,
                parent::ORIGIN_VERIFICATION_MESSAGE_KEY => self::MISSING_TOKEN_MESSAGE,
            ];
        }

        $expectedToken = $this->getConfig()->get(self::VERIFICATION_TOKEN);

        if (empty($expectedToken)) {
            throw new BotonomousException(self::MISSING_TOKEN_CONFIG_MESSAGE);
        }

        if ($token === $expectedToken) {
            return [
                parent::ORIGIN_VERIFICATION_SUCCESS_KEY => true,
                parent::ORIGIN_VERIFICATION_MESSAGE_KEY => 'Awesome!',
            ];
        }

        return [
            parent::ORIGIN_VERIFICATION_SUCCESS_KEY => false,
            parent::ORIGIN_VERIFICATION_MESSAGE_KEY => 'Token is not valid',
        ];
    }

    /**
     * @throws \Exception
     *
     * @return bool
     */
    public function isThisBot(): bool
    {
        $userId = $this->getRequest('user_id');
        $username = $this->getRequest('user_name');

        return ($userId == 'USLACKBOT' || $username == 'slackbot') ? true : false;
    }

    /**
     * @return string
     */
    public function getChannelId(): string
    {
        return $this->getRequest('channel_id');
    }
}
