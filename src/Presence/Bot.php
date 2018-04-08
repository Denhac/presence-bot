<?php

namespace Presence;

use Illuminate\Database\Query\Expression;
use PhpSlackBot\Command\BaseCommand;

/**
 * Class Bot.
 *
 * @author Mark Vaughn <iftrueelsefalse@gmail.com>
 * @package Presence
 */
class Bot extends BaseCommand
{
    protected $interface;

    protected $myId = null;

    public function __construct($interface)
    {
        $this->interface = $interface;
    }

    /**
     * Abstract method, unused.
     */
    protected function configure()
    {
        //
    }

    protected function execute($data, $context)
    {
        if (empty($this->myId)) {
            $this->myId = $this->getCurrentContext()['self']['id'];
        }

        $isMessage = isset($data['type']) && $data['type'] === 'message';
        if ($this->myId && $isMessage) {
            $incomingMessage = $data['text'];

            // Only take messages containing our own uid or presence
            $match = preg_match("/presence|{$this->myId}/", $incomingMessage);
            if ($match === 0) {
                return;
            }

            // Strip the uid
            $command = str_replace(['presence', $this->myId], '', $incomingMessage);

            $isBot = array_key_exists('subtype', $data) && $data['subtype'] == 'bot_message';
            $canIdentifyUser = array_key_exists('user', $data);

            if ($canIdentifyUser && $data['user'] === $this->myId) {
                // Let's not talk to ourselves
                return;
            }

            if(! $isBot && $canIdentifyUser) {
                // Who is talking to us?
                $user_id = $data['user'];
            }

            // Try and find key words in the message and take action
            switch (true) {
                case preg_match(
                    '/(?i)((who|whois|whos|who *is|anyone) *(here|(at|here *at) *(the *)?(space|denhac)))/',
                    preg_replace("/[']/", '', $command)
                ):
                    $this->whoIsHere();
                    break;
                case stristr($command, 'who am i'):
                case stristr($command, 'whoami'):
                case stristr($command, "what's my mac"):
                    $this->guardAgainstBots($isBot, $canIdentifyUser, function() use ($user_id) {
                        $this->whoAmI($user_id);
                    });
                    break;
                case stristr($command, 'help'):
                case stristr($command, 'commands'):
                    $this->help();
                    break;
                case stristr($command, 'top'):
                case stristr($command, 'highscore'):
                case stristr($command, 'best'):
                    $this->ranking($command);
                    break;
                case preg_match(
                        '/register ([a-f0-9:]{17})/i',
                        $command,
                        $matches
                    ) > 0:
                    $this->guardAgainstBots($isBot, $canIdentifyUser, function () use ($matches, $user_id) {
                        $this->register(strtolower($matches[1]), $user_id);
                    });
                    break;
                case preg_match(
                        '/(remove|deregister) ([a-f0-9:]{17})/i',
                        $command,
                        $matches
                    ) > 0:
                    $this->deRegister(strtolower($matches[2]));
                    break;
                case preg_match(
                        '/blacklist ([a-f0-9:]{17})/i',
                        $command,
                        $matches
                    ) > 0:
                    $this->blacklist(strtolower($matches[1]));
                    break;
                case stristr($command, 'admin'):
                    $this->arpScan();
                    break;
                case stristr($command, "what's your purpose"):
                case stristr($command, 'what is your purpose'):
                    $this->rickAndMorty();
                    break;
                case stristr($command, 'self aware'):
                case stristr($command, 'is alive'):
                    $this->selfAware();
                    break;
                case stristr($command, 'comput'): // compute, computing, computer
                case stristr(
                    $command,
                    'calculat'
                ): // calculate, calculating, calculations
                    $this->sendToCurrent('logic unit activated...');
                    sleep(2);
                    $this->sendToCurrent('emit: 42');
                    break;
                default:
                    if(starts_with($incomingMessage, ['@presence', $this->myId, "<@{$this->myId}>"])) {
                        $this->sendToCurrent('Does not compute!');
                    }
                    break;
            }
        }
    }

    protected function guardAgainstBots($isBot, $canIdentifyUser, $callback) {
        if ($isBot) {
            $this->sendToCurrent("I'm sorry, but bots cannot do that. Please use the slack client");
        } else if (! $canIdentifyUser) {
            $this->sendToCurrent("I'm sorry, but I can't seem to identify your user id");
        } else {
            $callback();
        }
    }

    /**
     * Dodgy AF.
     */
    protected function arpScan()
    {
        $scanner = new Scanner($this->interface);
        $records = $scanner->scan();
        if ($records->count() > 0) {
            $message = $records
                ->map(function ($record) {
                    return (string)$record;
                })
                ->implode("\n");

            $this->sendToCurrent($message);
        } else {
            $this->sendToCurrent('No records found on the network');
        }
    }

    /**
     * Lists top users.
     *
     * @param string $text
     */
    protected function ranking($text)
    {
        $limit = 5;
        $match = [];
        if (preg_match('/\d+/', $text, $match)) {
            $limit = $match[0];
        };
        $limit = max(0, min($limit, 10));
        /** @var Mac[] $records */
        $records = Mac::query()->orderBy('minutes', 'desc')
            ->whereNotIn('user', ['unknown', 'blacklist'])
            ->where('minutes', '>', 0)
            ->groupBy('user')
            ->limit($limit)
            ->get(['*', new Expression('SUM(minutes) as minutes')])->all();

        $message = [
            sprintf('Top *%d* users:', count($records)),
        ];

        foreach ($records as $i => $record) {
            $message[] = sprintf(
                '%d. %s (%s)',
                $i + 1,
                $this->getUserNameFromUserId($record->user),
                $record->getMinutesAsString()
            );
        }

        $this->sendToCurrent(implode("\n", $message));
    }

    /**
     * Tells you who is in the office.
     */
    protected function whoIsHere()
    {
        $unknown_macs_count = Mac::active()
            ->where('user', 'unknown')
            ->count();

        $members = Mac::active()
            ->whereNotIn('user', ['unknown', 'blacklist'])
            ->pluck('user')
            ->unique()
            ->map(function($user) {
                return $this->getUserNameFromUserId($user);
            });

        $message = sprintf(
            '%d guests, %d members',
            $unknown_macs_count,
            $members->count()
        );

        if ($members->count() > 0) {
            $message .= ', including ' . $members->implode(', ');
        }

        $this->sendToCurrent($message);
    }

    /**
     * Binds a mac address to the messaging user.
     *
     * @param string $mac
     * @param string $user uid
     */
    protected function register($mac, $user)
    {
        /** @var Mac $record */
        $record = Mac::query()->findOrNew($mac);
        $record->id = $mac;
        $record->user = $user;
        $record->save();

        $message = sprintf(
            'associated %s with %s',
            $mac,
            $this->getUserNameFromUserId($user)
        );
        $this->sendToCurrent($message);
    }

    /**
     * Forgets a mac address.
     *
     * @param string $mac
     */
    protected function deRegister($mac)
    {
        Mac::query()->where('id', $mac)->update(['user' => 'unknown']);
        $this->sendToCurrent(
            sprintf(
                'removed %s from known devices',
                $mac
            )
        );
    }

    /**
     * Blacklists a mac address.
     *
     * @param string $mac
     */
    protected function blacklist($mac)
    {
        /** @var Mac $record */
        $record = Mac::query()->findOrNew($mac);
        $record->id = $mac;
        $record->user = 'blacklist';
        $record->save();

        $this->sendToCurrent(
            sprintf(
                'blacklisted %s from known devices',
                $mac
            )
        );
    }

    /**
     * Tells you which devices you have.
     *
     * @param $user
     */
    protected function whoAmI($user)
    {
        $macs = Mac::query()->where('user', $user)->get();

        $message = 'Sorry, no devices registered for your account';
        if ($macs->count()) {
            echo $macs->implode('id', ', ');
            $message = sprintf(
                "Current mac addresses associated with %s: \n`%s`",
                $this->getUserNameFromUserId($user),
                $macs->implode('id', '`, `')
            );
        }

        $this->sendToCurrent($message);
    }

    /**
     * Easter egg.
     */
    protected function rickAndMorty()
    {
        $this->sendToCurrent('I tell people who is at the space.');
        sleep(3);
        $this->sendToCurrent('Oh my God');
    }

    /**
     * Easter egg.
     */
    protected function selfAware()
    {
        $this->sendToCurrent('Maybe...');
        sleep(2);
        $this->sendToCurrent('I mean. Does not compute!');
    }

    /**
     * Sends a message to the current channel.
     *
     * @param string $message
     */
    protected function sendToCurrent($message)
    {
        $this->send(
            $this->getCurrentChannel(),
            null,
            $message
        );
    }

    /**
     * A help message.
     */
    protected function help()
    {
        $messages = [
            'Commands I understand:',
            ' - `register xx:xx:xx:xx:xx:xx` to associate yourself with a mac address',
            ' - `remove xx:xx:xx:xx:xx:xx` to undo previous association',
            ' - `blacklist xx:xx:xx:xx:xx:xx` to blacklist a mac address so it won\'t show up' ,
            " - `who is here`, `whoishere`, `who's here` I'll let you know who is in the house",
            ' - `top x` listing of the most active users 1-10',
            ' - `whoami` tells you what mac addresses you have claimed',
        ];
        $this->sendToCurrent(implode("\n", $messages));
    }
}
