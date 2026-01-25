<?php

declare(strict_types=1);

namespace App\Helpers;

class ForgeLogger
{
    private static string $historyFile = BASE_PATH . '/.forge/history.json';
    private const MAX_HISTORY_ENTRIES = 30;

    /**
     * Adds an action to the history log.
     *
     * @param string $actionDescription A description of the action performed.
     */
    public static function logAction(string $actionDescription): void
    {
        if (!is_dir(dirname(self::$historyFile))) {
            mkdir(dirname(self::$historyFile), 0777, true);
        }

        $history = [];
        if (file_exists(self::$historyFile)) {
            $content = file_get_contents(self::$historyFile);
            if ($content !== false) {
                $history = json_decode($content, true);
                if (!is_array($history)) {
                    $history = [];
                }
            }
        }

        $newEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $actionDescription,
            'user' => Session::exists('user_name') ? Session::get('user_name') : 'System',
        ];

        array_unshift($history, $newEntry); // Add to the beginning
        $history = array_slice($history, 0, self::MAX_HISTORY_ENTRIES); // Trim to max entries

        file_put_contents(self::$historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    /**
     * Retrieves the history log.
     *
     * @return array An array of history entries.
     */
    public static function getHistory(): array
    {
        if (file_exists(self::$historyFile)) {
            $content = file_get_contents(self::$historyFile);
            if ($content !== false) {
                $history = json_decode($content, true);
                return is_array($history) ? $history : [];
            }
        }
        return [];
    }
}
