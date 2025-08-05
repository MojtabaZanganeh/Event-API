<?php
namespace Classes\Events;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;

class Medias extends Events
{
    use Base, Sanitizer;

    private function get_media_uuid_by_id($media_id)
    {
        $media_uuid = $this->getData("SELECT uuid FROM {$this->table['event_medias']} WHERE id = ?", [$media_id]);

        return $media_uuid['uuid'] ?? null;
    }

    public function upload_media()
    {
        $uploader = $this->check_role(['leader', 'admin']);

        $upload_dir = 'Uploads/';
        $uuid = $this->generate_uuid();
        $media_url = (isset($_FILES['media']) && $_FILES['media']['size'] > 0) ? $this->handle_file_upload($_FILES['media'], $upload_dir, $uuid) : null;

        $media_type = explode('/', $_FILES['media']['type'])[0];

        if (!$media_url) {
            Response::error('خطا در ذخیره تصویر');
        }

        $media_id = $this->insertData(
            "INSERT INTO {$this->table['event_medias']} (uuid, media_type, media_url, uploader_id, created_at) VALUES (?, ?, ?, ?, ?)",
            [
                $uuid,
                $media_type,
                $media_url,
                $uploader['id'],
                $this->current_time()
            ]
        );

        if (!$media_id) {
            Response::error('خطا در ثبت تصویر');
        }

        $media_uuid = $this->get_media_uuid_by_id($media_id);

        if (!$media_uuid) {
            Response::error('خطا در دریافت شناسه تصویر');
        }

        Response::success('تصویر آپلود شد', 'media_id', $media_uuid);
    }

    public function delete_media($params)
    {
        $uploader = $this->check_role(['leader', 'admin']);

        $this->check_params($params, ['media_id']);

        $delete_media = $this->deleteData(
            "DELETE FROM {$this->table['event_medias']} WHERE uuid = ? AND uploader_id = ?",
            [
                $params['media_id'],
                $uploader['id']
            ]
        );

        if (!$delete_media) {
            Response::error('خطا در حذف تصویر');
        }

        Response::success('تصویر حذف شد');
    }
}