<?php
namespace App\Services;
use App\Core\DB;
class NotificationService {
    public static function add(int $userId, ?int $journalId, string $type, string $message, ?string $assocType = null, ?int $assocId = null): void {
        DB::insert('notifications', ['user_id' => $userId, 'journal_id' => $journalId, 'type' => $type, 'assoc_type' => $assocType, 'assoc_id' => $assocId,
            'message' => $message, 'created_at' => date('Y-m-d H:i:s')]);
    }
    public static function unread(int $userId): int { return (int)DB::val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL', [$userId]); }
}
