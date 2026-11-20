<?php
namespace App\Services;
use App\Core\{DB, Settings};
use App\Models\SubmissionRepo;

/** OJS runs its scheduled tasks from page requests ("acron"); there is no cron on shared hosting, so this does the same: at most once an hour, after the response is sent.
 *  Tasks: automated review reminders (Workflow > Review > Setup) and clearing expired reset links. */
class ScheduledTaskService {
    const EVERY = 3600;
    public static function register(): void {
        register_shutdown_function(function () {
            if (PHP_SAPI === 'cli') return;
            if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
            try { self::runIfDue(); } catch (\Throwable $e) { \App\Core\Log::write('scheduled tasks: ' . $e->getMessage()); }
        });
    }
    public static function runIfDue(): void {
        $last = (int)Settings::site('lastScheduledRun', 0);
        if (time() - $last < self::EVERY) return;
        // claim the slot first so two simultaneous requests do not both run the tasks
        if (!DB::exec('UPDATE site_settings SET value=? WHERE name=? AND (value=? OR value IS NULL)', [(string)time(), 'lastScheduledRun', (string)$last])) {
            if ($last !== 0) return;
            DB::exec('INSERT IGNORE INTO site_settings (name, value) VALUES (?, ?)', ['lastScheduledRun', (string)time()]);
        }
        self::reviewReminders(); self::cleanup();
    }
    public static function reviewReminders(): int {
        $invite = (int)Settings::get('numDaysBeforeInviteReminder', 0); $submit = (int)Settings::get('numDaysBeforeSubmitReminder', 0); $sent = 0;
        $rows = [];
        if ($invite > 0) $rows = array_merge($rows, DB::all("SELECT ra.*, u.email, u.given_name, u.family_name FROM review_assignments ra JOIN users u ON u.id=ra.reviewer_id
            WHERE ra.status='awaiting_response' AND ra.date_response_due IS NOT NULL AND ra.date_reminded IS NULL AND NOW() >= DATE_ADD(ra.date_response_due, INTERVAL ? DAY)", [$invite]));
        if ($submit > 0) $rows = array_merge($rows, DB::all("SELECT ra.*, u.email, u.given_name, u.family_name FROM review_assignments ra JOIN users u ON u.id=ra.reviewer_id
            WHERE ra.status='accepted' AND ra.date_due IS NOT NULL AND ra.date_reminded IS NULL AND NOW() >= DATE_ADD(ra.date_due, INTERVAL ? DAY)", [$submit]));
        foreach ($rows as $ra) {
            $sub = DB::one('SELECT s.id, s.journal_id, p.title FROM submissions s JOIN publications p ON p.id=s.current_publication_id WHERE s.id=?', [$ra['submission_id']]);
            if (!$sub) continue;
            $name = trim($ra['given_name'] . ' ' . $ra['family_name']);
            EmailTemplateService::send('REVIEW_REMIND', $ra['email'], ['reviewerName' => $name, 'submissionTitle' => $sub['title'], 'reviewDueDate' => fmt_date($ra['date_due']),
                'reviewUrl' => abs_url(url('reviewer', 'submission', [$ra['id']]))], (int)$sub['id']);
            DB::exec('UPDATE review_assignments SET date_reminded=NOW() WHERE id=?', [$ra['id']]);
            NotificationService::add((int)$ra['reviewer_id'], (int)$sub['journal_id'], 'review_reminder', t('notif.review_reminder', $sub['title']), 'review_assignment', (int)$ra['id']);
            SubmissionRepo::log((int)$sub['id'], null, 'reviewer_reminded', $name . ' (' . t('set.automatic') . ')'); $sent++;
        }
        return $sent;
    }
    public static function cleanup(): void {
        DB::exec('DELETE FROM password_resets WHERE expires_at < NOW()');
        DB::exec('DELETE FROM sessions_remember WHERE expires_at < NOW()');
    }
}
