<?php
// Define routes for podcast configuration

$app->get('/configure', function () use ($app, $c) {
    $config = $c['PodcastConfig']->load();
    $app->view()->setData(array(
        'configuration' => $config
    ));
    $app->render('configure.html');
});

$app->post('/configure', function () use ($app, $c) {
    $data = $app->request()->post();
    $c['PodcastConfig']->save($data);
    $app->flash('success', 'Configuration Saved');
    $app->redirect('/configure');
});
