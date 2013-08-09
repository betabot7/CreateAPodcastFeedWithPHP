<?php
$app->get('/', function () use ($app) {  
    $app->redirect('/podcast'); 
});
