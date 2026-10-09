<?php
$events = []; $announcements = []; $content_error = '';
try {
    $items = database()->query('SELECT * FROM public_updates WHERE published=1 ORDER BY event_date, updated_at DESC, id DESC')->fetchAll();
    foreach ($items as $item) {
        if ($item['kind'] === 'event') $events[$item['id']] = ['date'=>$item['event_date'],'program'=>$item['program'],'title'=>$item['title'],'time'=>$item['event_time'],'venue'=>$item['venue'],'details'=>$item['body']];
        else $announcements[] = $item;
    }
} catch (PDOException $exception) {
    error_log('Request failed: '.basename(__FILE__));
    $content_error = 'Updates are temporarily unavailable. Please try again later.';
}
