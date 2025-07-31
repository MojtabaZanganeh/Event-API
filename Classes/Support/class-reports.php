<?php
namespace Classes\Support;
use Classes\Base\Base;
use Classes\Users\Users;
use Classes\Base\Response;
use Classes\Base\Sanitizer;

class Reports extends Users
{
    use Base, Sanitizer;

    public function add_report($params)
    {
        $this->check_params($params, [['reported_user_id', 'reported_conversation_id', 'reported_message_id', 'reported_event_id', 'reported_leader_id', 'reported_memory_id']]);

        $reporter = $this->check_role();

        $reported_user_id = $params['reported_user_id'] ?? null;
        $reported_conversation_id = $params['reported_conversation_id'] ?? null;
        $reported_message_id = $params['reported_message_id'] ?? null;
        $reported_event_id = $params['reported_event_id'] ?? null;
        $reported_leader_id = $params['reported_leader_id'] ?? null;
        $reported_memory_id = $params['reported_memory_id'] ?? null;
        $reason = $params['reason'] ?? null;

        $sql = "INSERT INTO {$this->table['reports']} (`reporter_id`, `reported_user_id`, `reported_conversation_id`, `reported_message_id`, `reported_event_id`, `reported_leader_id`, `reported_memory_id`, `reason`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $add_report = $this->insertData($sql, [$reporter['id'], $reported_user_id, $reported_conversation_id, $reported_message_id, $reported_event_id, $reported_leader_id, $reported_memory_id, $reason]);

        if (!$add_report) {
            Response::error('خطا در ثبت گزارش');
        }

        Response::success('گزارش ثبت شد');
    }
}