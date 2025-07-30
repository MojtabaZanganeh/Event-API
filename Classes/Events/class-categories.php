<?php
namespace Classes\Events;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;

class Categories extends Events
{
    use Base, Sanitizer;

    public function get_categories()
    {
        $categories = $this->getData("SELECT * FROM {$this->table['event_categories']}", [], true);

        if (!$categories) {
            Response::error('خطا در دریافت دسته بندی ها');
        }

        Response::success('دسته بندی رویدادها دریافت شد', 'allCategories', $categories);
    }

}