<?php

namespace Exporters\Pagespeed;

include __DIR__ . '/../vendor/autoload.php';

header('Content-Type: text/plain; version=0.0.4');

$uri = parse_url($_SERVER['REQUEST_URI']);
parse_str($uri['query'], $query);
$target = rtrim($query['target'], "/");

$audits = [];

foreach(glob(__DIR__ . '/../results/*.{json}', GLOB_BRACE) as $result) {
    $data = json_decode(file_get_contents($result), true);
    if(isset($data['lighthouseResult']['audits'])) {
        $lighthouse_result = $data['lighthouseResult'];
    } else {
        continue;
    }
    $strategy = $lighthouse_result['configSettings']['formFactor'];
    $url = rtrim($lighthouse_result['requestedUrl'], "/");

    if($url !== $target) {
        continue;
    }
    foreach($lighthouse_result['audits'] as $key => $audit) {
        if(!isset($audit['numericValue'])) {
            continue;
        }
        $key = str_replace('-', '_', $key);
        if(!isset($audits[$key])) {
            $audits[$key] = [];
        }
        switch($key) {
            default:
                $audits[$key][$strategy] = [
                    'help' => $audit['description'],
                    'value' => $audit['numericValue'],
                ];
        }
    }
}
foreach($audits as $audit_key => $audit) {
    if(count($audit) == 0) {
        continue;
    }
    $help = null;
    foreach($audit as $metric) {
        $help = $metric['help'];
        break;
    }
    if(!empty($help)) {
        echo '# HELP ' . $help . "\n";
    }
    echo '# TYPE ' . $audit_key . ' gauge' . "\n";
    foreach($audit as $strategy => $metric) {
        echo 'pagespeed_' . $audit_key . '{platform="' . $strategy . '"} ' . $metric['value'] . "\n";
    }
}




