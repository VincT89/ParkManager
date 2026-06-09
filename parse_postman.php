<?php
$json = file_get_contents('https://documenter.gw.postman.com/api/collections/15764785/TzRX9ReF?segregateAuth=true&versionTag=latest');
$data = json_decode($json, true);

function printUrls($items) {
    foreach($items as $item) {
        if (isset($item['item'])) {
            printUrls($item['item']);
        } else {
            $method = $item['request']['method'] ?? 'GET';
            $urlObj = $item['request']['url'] ?? [];
            if (is_string($urlObj)) {
                $url = $urlObj;
            } elseif (isset($urlObj['raw'])) {
                $url = $urlObj['raw'];
            } elseif (isset($urlObj['path'])) {
                $url = implode('/', $urlObj['path']);
            } else {
                $url = json_encode($urlObj);
            }
            $id = $item['id'] ?? 'unknown';
            echo "{$item['name']} [{$id}]: [{$method}] {$url}\n";
        }
    }
}

if (isset($data['item'])) {
    printUrls($data['item']);
}
