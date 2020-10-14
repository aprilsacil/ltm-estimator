<?php //-->
ini_set('memory_limit', '-1');

/**
 * This file is part of the Cradle PHP Kitchen Sink Faucet Project.
 * (c) 2016-2018 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use Cradle\Module\Utility\File;
use Cradle\Module\Utility\Validator;

/**
 * File Upload (supporting job)
 *
 * @param Request  $request
 * @param Response $response
 */
$cradle->on('utility-file-upload', function ($request, $response) {
    //get data
    $data = $request->getStage('data');

    //try cdn if enabled
    $s3 = $this->package('global')->service('s3-main');
    $upload = $this->package('global')->path('upload');

    //try cdn if enabled
    $data = File::base64ToS3($data, $s3);

    //try being old school
    $data = File::base64ToUpload($data, $upload);

    $response->setError(false)->setResults([
        'data' => $data
    ]);
});

/**
 * Cost Calculator
 *
 * @param Request  $request
 * @param Response $response
 */
$cradle->on('utility-cost-calculator', function ($request, $response) {
    $data = $request->getStage();

    // validate fields
    $this->method('validate-cost-calculator', $request, $response);

    if ($response->isError()) {
        return $response;
    }

    // it went here meaning it passed the validation, proceed with computing
    // get costing
    $costing = $this->package('global')->config('costs');
    $storagePerMb = $costing['storage_per_gb_month'] / 1024; // 1gb is 1024mb
    $storageConsumed = $ramConsumed = $totalCost = 0;
    $casesTotal = $data['cases'];
    $growth = $data['growth'] / 100;
    $breakdown = [];

    for ($i = 1; $i <= $data['months']; $i++) {
        if ($i != 1) {
            $casesTotal += $casesTotal * $growth;
        }

        $storageConsumed = $casesTotal * 10;
        $ramConsumed = $casesTotal / 2; // we divide it by 2 since 1000 study consumes 500 mb of ram, which also equates to 2 study/ram
        // get last day of the month multiply by 24hrs to get the total hours of the Month
        $currentMonthHrs = date('t', strtotime('+' . $i-1 .' months')) * 24; 
        $ramPerMb = ($costing['ram_per_gb_hr'] / 1024) * $currentMonthHrs;
        
        $cost = round(($storageConsumed * $storagePerMb) + ($ramConsumed * $ramPerMb), 2);
        $breakdown[$i] = [
            'month' => date('F Y', strtotime('+' . $i-1 .' months')),
            'ram_consumed_mb' => number_format($ramConsumed),
            'storage_consumed_mb' => number_format($storageConsumed),
            'cases' => number_format($casesTotal),
            'cost' => '$ ' . number_format($cost, 2)
        ];

        $totalCost += $cost;
    }

    $results = [
        'total_cases' => number_format($casesTotal),
        'total_cost' => '$ ' . number_format($totalCost, 2),
        'total_ram_consumed_mb' => number_format($ramConsumed),
        'total_storage_consumed_mb' => number_format($storageConsumed),
        'breakdown' => $breakdown,
    ];

    $response->setResults($results);
});

/**
 * Cost Calculator
 *
 * @param Request  $request
 * @param Response $response
 */
$cradle->on('validate-cost-calculator', function ($request, $response) {
    $data = $request->getStage();
    $errors = [];

    // server-side validation
    // check if cases is given and valid
    if (!isset($data['cases']) || empty($data['cases'] || !Validator::isInteger($data['cases']))) {
        $errors['cases'] = 'Please provide valid number of cases';
    }
    // check if growth is given and valid
    if (!isset($data['growth']) || empty($data['growth'] || !Validator::isFloat($data['growth']))) {
        $errors['growth'] = 'Please provide valid number of growth percentage';
    }
    // check if month is given and valid
    if (!isset($data['months']) || empty($data['months'] || !Validator::isInteger($data['months']))) {
        $errors['months'] = 'Please provide valid number of months to forecast';
    }

    if ($errors) {
        return $response->setError(true, 'Invalid parameters')
            ->set('json', 'validation', $errors);
    }
});