<?php

namespace Botonomous\utility;

use Botonomous\BotonomousException;
use Botonomous\CommandContainer;

/**
 * Class MessageUtility.
 */
class MessageUtility extends AbstractUtility
{
    /**
     * Remove the mentioned bot username from the message.
     *
     * @param $message
     *
     * @throws \Exception
     *
     * @return string
     */
    public function removeMentionedBot(string $message): string
    {
        $userLink = $this->getUserLink();

        return preg_replace("/{$userLink}/", '', $message, 1);
    }

    /**
     * Check if the bot user id is mentioned in the message.
     *
     * @param $message
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function isBotMentioned(string $message): bool
    {
        $userLink = $this->getUserLink();

        return (new StringUtility())->findInString($userLink, $message, false);
    }

    /**
     * Return command name in the message.
     *
     * @param string $message
     *
     * @return null|string
     * @throws \Exception
     */
    public function extractCommandName(string $message)
    {
        // remove the bot mention if it exists
        $message = $this->removeMentionedBot($message);

        /**
         * Command must start with / and at the beginning of the sentence.
         */
        $commandPrefix = $this->getConfig()->get('commandPrefix');
        $commandPrefix = preg_quote($commandPrefix, '/');

        $pattern = '/^('.$commandPrefix.'\w{1,})/';

        preg_match($pattern, ltrim($message), $groups);

        // If command is found, remove command prefix from the beginning of the command
        return isset($groups[1]) ? ltrim($groups[1], $commandPrefix) : null;
    }

    /**
     * Return command details in the message.
     *
     * @param string $message
     *
     * @return \Botonomous\Command|null
     * @throws \Exception
     */
    public function extractCommandDetails(string $message)
    {
        // first get the command name
        $command = $this->extractCommandName($message);

        // then get the command details
        return (new CommandContainer())->getAsObject($command);
    }

    /**
     * @param $triggerWord
     * @param $message
     *
     * @return string
     */
    public function removeTriggerWord(string $message, string $triggerWord)
    {
        $count = 1;

        return ltrim(str_replace($triggerWord, '', $message, $count));
    }

    /**
     * @param        $userId
     * @param string $userName
     *
     * @throws \Exception
     *
     * @return string
     */
    public function linkToUser(string $userId, string $userName = '')
    {
        if (empty($userId)) {
            throw new BotonomousException('User id is not provided');
        }

        if (!empty($userName)) {
            $userName = "|{$userName}";
        }

        return "<@{$userId}{$userName}>";
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getUserLink(): string
    {
        return $this->linkToUser($this->getConfig()->get('botUserId'));
    }

    /**
     * @param array $keywords
     * @param       $message
     *
     * @return array
     */
    public function keywordPos(array $keywords, string $message): array
    {
        $found = [];
        if (empty($keywords)) {
            return $found;
        }

        foreach ((new ArrayUtility())->sortArrayByLength($keywords) as $keyword) {
            foreach ((new StringUtility())->findPositionInString($keyword, $message) as $position) {
                // check if the keyword does not overlap with one of the already found
                if ($this->isPositionTaken($found, $position) === false) {
                    $found[$keyword][] = $position;
                }
            }
        }

        return $found;
    }

    /**
     * @param array $keywords
     * @param       $message
     *
     * @return array|void
     */
    public function keywordCount(array $keywords, string $message)
    {
        $keysPositions = $this->keywordPos($keywords, $message);

        if (empty($keysPositions)) {
            return;
        }

        foreach ($keysPositions as $key => $positions) {
            $keysPositions[$key] = count($positions);
        }

        return $keysPositions;
    }

    /**
     * @param array $tokensPositions
     * @param       $newPosition
     *
     * @return bool
     */
    private function isPositionTaken(array $tokensPositions, int $newPosition)
    {
        if (empty($tokensPositions)) {
            return false;
        }

        foreach ($tokensPositions as $token => $positions) {
            $tokenLength = strlen($token);
            if ($this->isPositionIn($newPosition, $positions, $tokenLength) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $newPosition
     * @param $positions
     * @param $tokenLength
     *
     * @return bool
     */
    private function isPositionIn(int $newPosition, array $positions, int $tokenLength)
    {
        foreach ($positions as $position) {
            if ($newPosition >= $position && $newPosition < $position + $tokenLength) {
                return true;
            }
        }

        return false;
    }
}
