<?php
// define podcast and episode routes

$app->get('/episode', function () use ($app) {  
    $app->render('episode-add.html'); 
});

$app->post('/episode', function () use ($app, $c) {  
    $db = $c['db'];
    $data = $app->request()->post();
    $dir = $c['config']['path.uploads'];

    $filepath = $dir . basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $filepath);

    $id = $db->episodes->insert(array(
        'title'       =>  $data['title'],
        'author'      =>  $data['author'],
        'summary'     =>  $data['summary'],
        'description' =>  $data['description'],
        'audio_file'  =>  $filepath,
        'created'     =>  time()
    ));

    $app->flash('success', 'Episode Created');
    $app->redirect('/podcast');
});

$app->get('/podcast', function () use ($app, $c) {  
    $db = $c['db'];
    $app->view()->setData(array(
        'podcast' => $db->episodes()->order('created DESC')
    ));
    $app->render('podcast.html');
});

$app->get('/podcast.xml', function () use ($app, $c) {
    $db = $c['db'];
    $conf = $c['PodcastConfig']->load();

    $xml = new DOMDocument();
    $root = $xml->appendChild($xml->createElement('rss'));
    $root->setAttribute('xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
    $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
    $root->setAttribute('xmlns:feedburner', 'http://rssnamespace.org/feedburner/ext/1.0');
    $root->setAttribute('version', '2.0');

    $link = sprintf(
        '%s://%s/podcast', 
        $app->request()->getScheme(),
        $app->request()->getHost()
    );

    $chan = $root->appendChild($xml->createElement('channel'));
    $chan->appendChild($xml->createElement('title', $conf['title']));
    $chan->appendChild($xml->createElement('link', $link));
    $chan->appendChild($xml->createElement('generator', 'PHPMaster Podcast Tutorial'));
    $chan->appendChild($xml->createElement('language', $conf['language']));
    $chan->appendChild($xml->createElement('copyright', $conf['copyright']));
    $chan->appendChild($xml->createElement('itunes:subtitle', $conf['subtitle']));
    $chan->appendChild($xml->createElement('itunes:author', $conf['author']));
    $chan->appendChild($xml->createElement('itunes:summary', $conf['summary']));
    $chan->appendChild($xml->createElement('description', $conf['description']));

    $owner = $chan->appendChild($xml->createElement('itunes:owner'));
    $owner->appendChild($xml->createElement('name', $conf['owner_name']));
    $owner->appendChild($xml->createElement('email', $conf['owner_email']));    

    if (count($conf['categories'])) {
        foreach ($conf['categories'] as $category) {
            if ($pos = strpos($category, '|')) {
                $cat = $chan->appendChild($xml->createElement('itunes:category'));
                $cat->setAttribute('text', substr($category, 0, $pos)); 
                $subcat = $cat->appendChild($xml->createElement('itunes:category'));
                $subcat->setAttribute('text', substr($category, ++$pos));
            }
            else {
                $cat = $chan->appendChild($xml->createElement('itunes:category'));
                $cat->setAttribute('text', $category);
            }
        }
    }

    $chan->appendChild($xml->createElement('itunes:keywords', $conf['keywords']));
    $chan->appendChild($xml->createElement('itunes:explicit', $conf['explicit']));
    $chan->appendChild($xml->createElement('lastbuilddate', date('D, d M Y H:i:s O')));

  
    foreach ($db->episodes()->order('created ASC') as $episode) {
        $audioURL = sprintf(
            '%s://%s/uploads/%s', 
            $app->request()->getScheme(),
            $app->request()->getHost(),
            basename($episode['audio_file'])
        );

        $item = $chan->appendChild($xml->createElement('item'));
        $item->appendChild($xml->createElement('title', $episode['title']));
        $item->appendChild($xml->createElement('link', $audioURL));
        $item->appendChild($xml->createElement('itunes:author', $episode['title']));
        $item->appendChild($xml->createElement('itunes:summary', $episode['summary']));
        $item->appendChild($xml->createElement('guid', $audioURL));

        $finfo = finfo_open(FILEINFO_MIME_TYPE); 
        $enclosure = $item->appendChild($xml->createElement('enclosure'));
        $enclosure->setAttribute('url', $episode['audio_file']);
        $enclosure->setAttribute('length', filesize($episode['audio_file']));
        $enclosure->setAttribute('type', finfo_file($finfo, $episode['audio_file']));
      
        $item->appendChild($xml->createElement('pubDate', date('D, d M Y H:i:s O', $episode['created'])));

        $getID3 = new getID3();
        $fileinfo = $getID3->analyze($episode['audio_file']);
        $item->appendChild($xml->createElement('itunes:duration', $fileinfo['playtime_string']));
    }

    $xml->formatOutput = true;

    $res= $app->response();
    $res['Content-Type'] = 'application/json';
    print $xml->saveXML();
});
