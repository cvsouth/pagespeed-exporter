<?php

namespace Exporters\Pagespeed;

include __DIR__ . '/../vendor/autoload.php';

header('Content-Type: text/plain; version=0.0.4');

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

    foreach($lighthouse_result['audits'] as $key => $audit) {
        if(!isset($audit['numericValue'])) {
            continue;
        }
        $key = str_replace('-', '_', $key);
        if(!isset($audits[$key])) {
            $audits[$key] = [];
        }
        if(!isset($audits[$key][$strategy])) {
            $audits[$key][$strategy] = [];
        }
        switch($key) {
            default:
                $audits[$key][$strategy][$url] = [
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
    foreach($audit as $strategy) {
        foreach($strategy as $metric) {
            $help = $metric['help'];
            break 2;
        }
    }
    if(!empty($help)) {
        echo '# HELP ' . $help . "\n";
    }
    echo '# TYPE ' . $audit_key . ' gauge' . "\n";
    foreach($audit as $strategy_name => $strategy) {
        foreach($strategy as $url => $metric) {
            echo 'pagespeed_' . $audit_key . '{instance="' . $url . '",platform="' . $strategy_name . '"} ' . $metric['value'] . "\n";
        }
    }
}




