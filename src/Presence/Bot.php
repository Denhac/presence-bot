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

    protected function configure()
    {
    }

    protected function execute($data, $context)
    {

        if (empty($this->myId)) {
            $this->myId = $this->getCurrentContext()['self']['id'];
        }

        $isMessage = isset($data['type']) && $data['type'] === 'message';
        if ($this->myId && $isMessage) {
            $text = $data['text'];

            // Only take messages containing our own uid or presence
            $match = preg_match("/presence|{$this->myId}/", $text, $test);
            if ($match === 0) {
                return;
            }

            // Strip the uid
            $text = str_replace(['presence', $this->myId], '', $text);

            // Who is talking to us?
            $user = $data['user'];
            if ($user === $this->myId) {
                // Let's not talk to ourselves
                return;
            }

            // Try and find key words in the message and take action
            switch (true) {
                case stristr($text, 'whoishere'):
                case stristr($text, 'who is here'):
                case stristr($text, "who's here"):
                    $this->whoIsHere();
                    break;
                case stristr($text, 'who am i'):
                case stristr($text, 'whoami'):
                case stristr($text, "what's my mac"):
                    $this->whoAmI($user);
                    break;
                case stristr($text, 'help'):
                case stristr($text, 'commands'):
                    $this->help();
                    break;
                case stristr($text, 'top'):
                case stristr($text, 'highscore'):
                case stristr($text, 'best'):
                    $this->ranking($text);
                    break;
                case preg_match(
                        '/register ([a-f0-9:]{17})/i',
                        $text,
                        $matches
                    ) > 0:
                    $this->register(strtolower($matches[1]), $user);
                    break;
                case preg_match(
                        '/(remove|deregister) ([a-f0-9:]{17})/',
                        $text,
                        $matches
                    ) > 0:
                    $this->deRegister($matches[2]);
                    break;
                case stristr($text, 'admin'):
                    $this->arpScan();
                    break;
                default:
                    $this->sendToCurrent("Does not compute!");
                    break;
            }
        }
    }

    protected function arpScan()
    {
        $scanner = new Scanner($this->interface);
        $records = $scanner->scan();
        $messages = array_map(
            function (ScanRecord $record) {
                return sprintf(
                    "Found device with mac address `%s` (%s) and ip `%s`",
                    $record->mac,
                    $record->description,
                    $record->ip
                );
            },
            $records
        );
        if (count($messages)) {
            $this->sendToCurrent(
                implode("\n", $messages)
            );
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
            ->where('user', '<>', 'unknown')
            ->groupBy('user')
            ->limit($limit)
            ->get(['*', new Expression('SUM(minutes) as minutes')])->all();

        $message = [
            sprintf('Top *%d* users:', $limit),
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
        $scanner = new Scanner($this->interface);
        $records = $scanner->scan();
        $macs = array_map(
            function (ScanRecord $record) {
                return $record->mac;
            },
            $records
        );
        $found = Mac::query()->whereIn('id', $macs)
            ->limit(50)
            ->groupBy('user')
            ->where('user', '<>', 'unknown')
            ->get();

        $total = count($records);
        $members = $found->count();
        $guests = $total - $members;
        $message = sprintf(
            '%d guests, %d members',
            $guests,
            $members
        );

        if ($members) {
            $found->each(
                function (Mac $record) {
                    $record->user = $this->getUserNameFromUserId($record->user);
                }
            );
            $message .= ', including ' . $found->implode('user', ', ');
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
            " - `who is here`, `whoishere`, `who's here` I'll let you know who is in the house",
            ' - `top x` listing of the most active users 1-10',
            ' - `whoami` tells you what mac addresses you have claimed',
        ];
        $this->sendToCurrent(implode("\n", $messages));
    }
}
