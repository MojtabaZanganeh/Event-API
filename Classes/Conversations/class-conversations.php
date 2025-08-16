<?php
namespace Classes\Conversations;
use Classes\Base\Database;
use Classes\Users\Users;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use DateTime;

class Conversations extends Users
{
    use Base, Sanitizer;

    public function get_user_conversations()
    {
        $user = $this->check_role();

        $sql =
            "SELECT 
                c.id AS id,
                CASE 
                    WHEN c.is_group = TRUE THEN c.name
                    ELSE (
                        SELECT CONCAT(u2.first_name, ' ', u2.last_name) AS name
                        FROM {$this->table['conversation_participants']} cp2
                        JOIN {$this->table['users']} u2 ON cp2.user_id = u2.id
                        WHERE cp2.conversation_id = c.id AND cp2.user_id != ?
                        LIMIT 1
                    )
                END AS `name`,
                CASE 
                    WHEN c.is_group = TRUE THEN (
                        SELECT e.thumbnail_id FROM {$this->table['events']} e
                        WHERE e.id = c.event_id
                    )
                    ELSE (
                        SELECT u2.avatar AS avatar
                        FROM {$this->table['conversation_participants']} cp2
                        JOIN {$this->table['users']} u2 ON cp2.user_id = u2.id
                        WHERE cp2.conversation_id = c.id AND cp2.user_id != ?
                        LIMIT 1
                    )
                END AS avatar,
                c.is_group,
                c.expires_on,
                 JSON_OBJECT(
                    'text', m.text,
                    'time', m.created_at
                ) AS last_message,
                CONCAT(u.first_name, ' ', u.last_name) AS last_sender_name
            FROM {$this->table['conversation_participants']} cp
            JOIN {$this->table['conversations']} c ON cp.conversation_id = c.id
            LEFT JOIN {$this->table['messages']} m ON m.id = (
                SELECT m2.id
                FROM {$this->table['messages']} m2
                WHERE m2.conversation_id = c.id
                ORDER BY m2.created_at DESC
                LIMIT 1
            )
            LEFT JOIN {$this->table['users']} u ON m.sender_id = u.id
            WHERE cp.user_id = ? AND c.status = 'completed'
            ORDER BY 
                CASE 
                    WHEN c.expires_on >= NOW() THEN 0
                    ELSE 1
                END ASC,
            m.created_at DESC;";

        $conversations = $this->getData($sql, [$user['id'], $user['id'], $user['id']], true);

        if (!$conversations) {
            Response::success('گفتگویی یافت نشد');
        }

        foreach ($conversations as &$conversation) {
            $conversation['last_message'] = json_decode($conversation['last_message']);
        }

        Response::success('گفتگوها دریافت شد', 'allConversations', $conversations);
    }

    public function get_conversation_messages($params)
    {
        $user = $this->check_role();
        $this->check_params($params, ['conversation_id']);

        $conversation_id = $params['conversation_id'];
        $user_id = $user['id'];

        $conversation = $this->getData(
            "SELECT is_group FROM {$this->table['conversations']} WHERE id = ?",
            [$conversation_id]
        );

        if (!$conversation) {
            Response::success('چت مورد نظر یافت نشد');
        }

        $is_group = $conversation['is_group'];

        $messages_json = $this->getData(
            "SELECT 
                    JSON_OBJECT(
                        'id', m.id,
                        'conversation_id', m.conversation_id,
                        'expires_on', c.expires_on,
                        'text', m.text,
                        'time', DATE_FORMAT(m.created_at, '%Y/%m/%d %H:%i'),
                        'is_self', (m.sender_id = ?),
                        'sender', CASE 
                            WHEN ? = 1 THEN JSON_OBJECT(
                                'name', CONCAT(us.first_name, ' ', us.last_name),
                                'avatar', us.avatar
                            )
                            ELSE CONCAT(us.first_name, ' ', us.last_name)
                        END,
                        'reply_to', CASE 
                            WHEN m.reply_to IS NOT NULL THEN JSON_OBJECT(
                                'id', r.id,
                                'sender', CONCAT(ur.first_name, ' ', ur.last_name),
                                'text', r.text
                            )
                            ELSE NULL
                        END
                    ) AS message_data

                FROM {$this->table['messages']} m

                LEFT JOIN {$this->table['messages']} r ON m.reply_to = r.id
                LEFT JOIN {$this->table['conversations']} c ON m.conversation_id = c.id
                LEFT JOIN {$this->table['users']} ur ON r.sender_id = ur.id
                LEFT JOIN {$this->table['users']} us ON m.sender_id = us.id

                WHERE m.conversation_id = ?

                ORDER BY m.id ASC
            ",
            [$user_id, $is_group, $conversation_id],
            true
        );

        if (!$messages_json) {
            Response::success('هنوز پیامی ارسال نکرده‌اید');
        }

        $messages = array_column($messages_json, 'message_data');

        foreach ($messages as &$message) {
            $message = json_decode($message, true);
        }

        Response::success('پیام‌ها دریافت شد', 'allConversationMessages', $messages);
    }

    public function send_message_to_conversation($params)
    {
        $sender = $this->check_role();
        $this->check_params($params, ['conversation_id', 'text']);

        $conversation_expires = $this->getData(
            "SELECT expires_on FROM {$this->table['conversations']} WHERE id = ?",
            [$params['conversation_id']]
        );

        if ($conversation_expires && $conversation_expires['expires_on'] >= new DateTime()) {
            $conversation_id = $params['conversation_id'];
            $text = $params['text'];
            $reply_to = $params['reply_to'] ?? null;
            $sender_id = $sender['id'];
            $now = $this->current_time();

            $message_id = $this->insertData(
                "INSERT INTO {$this->table['messages']} (`conversation_id`, `sender_id`, `text`, `reply_to`, `created_at`) VALUES (?, ?, ?, ?, ?)",
                [$conversation_id, $sender_id, $text, $reply_to, $now]
            );

            if ($message_id) {
                Response::success('پیام ارسال شد', 'sent_message', [
                    'id' => $message_id,
                    'sender' => $sender['first_name'] . ' ' . $sender['last_name'],
                    'avatar' => $sender['avatar']
                ]);
            }
        }

        Response::error('خطا در ارسال پیام');
    }

    public function create_conversation($conversation_name, $is_group, $event_id, $conversation_size, $expires_date, Database $db)
    {
        $conversation_id = $db->insertData(
            "INSERT INTO {$db->table['conversations']} (`name`, `is_group`, `event_id`, `conversation_size`, `status`, `expires_on`, `created_at`) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $is_group,
                $conversation_name,
                $event_id,
                $conversation_size,
                'pending',
                $expires_date,
                $this->current_time()
            ]
        );

        if (!$conversation_id) {
            return false;
        }

        return true;
    }

    private function completed_conversation($conversation_id, Database $db)
    {
        $update_conversation = $db->updateData(
            "UPDATE {$db->table['conversations']} 
                        SET `status` = 'completed', `updated_at` = ? 
                        WHERE id = ?",
            [
                $this->current_time(),
                $conversation_id
            ]
        );

        if ($update_conversation) {
            return true;
        }

        return false;
    }

    public function add_user_to_conversation($db, $conversation_id, $user_id, $reservation_id, $group_members_count)
    {
        $check_user_in_conversation = $db->getData(
            "SELECT id FROM {$db->table['conversation_participants']} WHERE `conversation_id` = ? AND `user_id` = ?",
            [
                $conversation_id,
                $user_id
            ]
        );

        if ($check_user_in_conversation) {
            return false;
        }

        $conversation_data = $db->getData(
            "SELECT 
                c.conversation_size,
                COALESCE(SUM(r.group_members), 0) AS current_members
            FROM {$db->table['conversations']} c
            LEFT JOIN {$db->table['conversation_participants']} cp ON c.id = cp.conversation_id
            LEFT JOIN {$db->table['reservations']} r ON cp.reservation_id = r.id
            WHERE c.id = ?
            GROUP BY c.id",
            [$conversation_id]
        );

        if (!$conversation_data) {
            return false;
        }

        $required_size = $conversation_data['conversation_size'];
        $current_members = $conversation_data['current_members'];
        $new_total_members = $current_members + $group_members_count;

        if ($new_total_members > $required_size) {
            return false;
        }

        $result = $db->insertData(
            "INSERT INTO {$db->table['conversation_participants']} 
                (`conversation_id`, `user_id`, `reservation_id`, `joined_at`) 
                VALUES (?, ?, ?, ?)",
            [
                $conversation_id,
                $user_id,
                $reservation_id,
                $this->current_time()
            ]
        );

        if (!$result) {
            return false;
        }

        if ($new_total_members === $required_size) {
            $update_result = $this->completed_conversation($conversation_id, $db);

            if (!$update_result) {
                return false;
            }
        }

        return true;
    }

    private function update_conversation_status($conversation_id, $new_status, $db)
    {
        $update_conversation = $db->updateData(
            "UPDATE {$db->table['conversations']} SET `status` = ? WHERE id = ?",
            [
                $new_status,
                $conversation_id
            ]
        );

        if ($update_conversation) {
            return true;
        }

        return false;
    }
}