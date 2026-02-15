<?php

// Define the mock config data
$mockConfig = [
    "hours"  => [72],
    "days"   => [7, 21],
    "months" => [1, 2, 3, 6, 9, 11],
    "years"  => [1, 2, 3, 4],
    "email_recipients" => "test@example.com",
    "email_subject"    => "Test Subject",
    "email_from"       => "test@example.com",
    "email_reply_to"   => "test@example.com",
    "fellowship_name"  => "Test Fellowship",
    "smtp_host"     => "smtp.test.com",
    "smtp_port"     => 465,
    "smtp_username" => "testuser",
    "smtp_password" => "testpass",
    "smtp_encryption" => "ssl",
];

// Create a temporary config.json file for testing
$configFilePath = __DIR__ . '/../config.json';
file_put_contents($configFilePath, json_encode($mockConfig, JSON_PRETTY_PRINT));

// Mock the fetch_csv.php file
// This will be the $rows variable in find_anniversaries.php
$rows = [
    // Header row
    ['Timestamp', 'Email Address', 'Name', 'Phone Number (WhatsApp preferred)', 'Sobriety Start Date', 'Gender', 'Location (State in India)', 'Nica India Helpline opt-in (Yes/No)', 'Anniversary share with fellowship (Yes/No)'],

    // Test Case 1: 72 hours anniversary (3 days)
    ['', 'test1@example.com', 'User 1', '1111111111', '2024-01-01 00:00:00', 'Male', 'MH', 'Yes', 'Yes'],

    // Test Case 2: 7 days anniversary
    ['', 'test2@example.com', 'User 2', '2222222222', '2023-12-26 00:00:00', 'Female', 'DL', 'No', 'Yes'],

    // Test Case 3: 1 month anniversary
    ['', 'test3@example.com', 'User 3', '3333333333', '2023-12-01 00:00:00', 'Male', 'KA', 'Yes', 'Yes'],

    // Test Case 4: 1 year anniversary
    ['', 'test4@example.com', 'User 4', '4444444444', '2023-01-01 00:00:00', 'Female', 'UP', 'Yes', 'Yes'],

    // Test Case 5: No consent
    ['', 'test5@example.com', 'User 5', '5555555555', '2024-01-01 00:00:00', 'Male', 'MH', 'Yes', 'No'],

    // Test Case 6: No anniversary today
    ['', 'test6@example.com', 'User 6', '6666666666', '2023-01-02 00:00:00', 'Female', 'DL', 'Yes', 'Yes'],

    // Test Case 7: Invalid date format (should be skipped)
    ['', 'test7@example.com', 'User 7', '7777777777', 'Invalid Date', 'Male', 'KA', 'Yes', 'Yes'],

    // Test Case 8: Future date (should be skipped)
    ['', 'test8@example.com', 'User 8', '8888888888', '2025-01-01 00:00:00', 'Female', 'UP', 'Yes', 'Yes'],

    // Test Case 9: 21 days anniversary
    ['', 'test9@example.com', 'User 9', '9999999999', '2023-12-12 00:00:00', 'Male', 'MH', 'Yes', 'Yes'],

    // Test Case 10: 2 years anniversary
    ['', 'test10@example.com', 'User 10', '1010101010', '2022-01-01 00:00:00', 'Female', 'DL', 'Yes', 'Yes'],

    // Test Case 11: 3 months anniversary
    ['', 'test11@example.com', 'User 11', '1111111111', '2023-10-01 00:00:00', 'Male', 'KA', 'Yes', 'Yes'],
];

// Define the test date
$testToday = new DateTime('2024-01-04 00:00:00'); // This will be injected into find_anniversaries.php

// Include the script to be tested
$anniversaries = include 'find_anniversaries.php';

// --- Assertions ---

echo "Running tests for find_anniversaries.php...\n";

// Expected matches for the given $testToday (2024-01-04)
$expectedMatches = [
    [
        'name'           => 'User 1',
        'email'          => 'test1@example.com',
        'phone'          => '1111111111',
        'sobriety_start' => '2024-01-01',
        'gender'         => 'Male',
        'location'       => 'MH',
        'helpline_optin' => 'Yes',
        'share_anniv'    => 'Yes',
        'milestones'     => ['72 hours'],
    ],
    [
        'name'           => 'User 2',
        'email'          => 'test2@example.com',
        'phone'          => '2222222222',
        'sobriety_start' => '2023-12-26',
        'gender'         => 'Female',
        'location'       => 'DL',
        'helpline_optin' => 'No',
        'share_anniv'    => 'Yes',
        'milestones'     => ['7 days'],
    ],
    [
        'name'           => 'User 3',
        'email'          => 'test3@example.com',
        'phone'          => '3333333333',
        'sobriety_start' => '2023-12-01',
        'gender'         => 'Male',
        'location'       => 'KA',
        'helpline_optin' => 'Yes',
        'share_anniv'    => 'Yes',
        'milestones'     => ['1 months'],
    ],
    [
        'name'           => 'User 4',
        'email'          => 'test4@example.com',
        'phone'          => '4444444444',
        'sobriety_start' => '2023-01-01',
        'gender'         => 'Female',
        'location'       => 'UP',
        'helpline_optin' => 'Yes',
        'share_anniv'    => 'Yes',
        'milestones'     => ['1 years'],
    ],
    [
        'name'           => 'User 9',
        'email'          => 'test9@example.com',
        'phone'          => '9999999999',
        'sobriety_start' => '2023-12-12',
        'gender'         => 'Male',
        'location'       => 'MH',
        'helpline_optin' => 'Yes',
        'share_anniv'    => 'Yes',
        'milestones'     => ['21 days'],
    ],
    [
        'name'           => 'User 10',
        'email'          => 'test10@example.com',
        'phone'          => '1010101010',
        'sobriety_start' => '2022-01-01',
        'gender'         => 'Female',
        'location'       => 'DL',
        'helpline_optin' => 'Yes',
        'share_anniv'    => 'Yes',
        'milestones'     => ['2 years'],
    ],
    [
        'name'           => 'User 11',
        'email'          => 'test11@example.com',
        'phone'          => '1111111111',
        'sobriety_start' => '2023-10-01',
        'gender'         => 'Male',
        'location'       => 'KA',
        'helpline_optin' => 'Yes',
        'share_anniv'    => 'Yes',
        'milestones'     => ['3 months'],
    ],
];

// Sort both arrays for consistent comparison
usort($anniversaries, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
usort($expectedMatches, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});


if (count($anniversaries) === count($expectedMatches)) {
    echo "Test Passed: Correct number of anniversaries found (" . count($anniversaries) . ").\n";
} else {
    echo "Test Failed: Expected " . count($expectedMatches) . " anniversaries, but found " . count($anniversaries) . ".\n";
    echo "Found:\n";
    print_r($anniversaries);
    echo "Expected:\n";
    print_r($expectedMatches);
    // Clean up the temporary config.json before exiting on failure
    unlink($configFilePath);
    exit(1);
}

foreach ($expectedMatches as $key => $expected) {
    if (!isset($anniversaries[$key])) {
        echo "Test Failed: Expected match at index $key not found.\n";
        print_r($expected);
        // Clean up the temporary config.json before exiting on failure
        unlink($configFilePath);
        exit(1);
    }
    $actual = $anniversaries[$key];
    if ($actual !== $expected) {
        echo "Test Failed: Mismatch for user " . $expected['name'] . ".\n";
        echo "Expected:\n";
        print_r($expected);
        echo "Actual:\n";
        print_r($actual);
        // Clean up the temporary config.json before exiting on failure
        unlink($configFilePath);
        exit(1);
    }
}

echo "All tests passed successfully!\n";

// Clean up the temporary config.json
unlink($configFilePath);

?>