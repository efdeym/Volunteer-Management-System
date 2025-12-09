<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataFile = __DIR__ . '/../data/projects.json';

if (!file_exists($dataFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Project data file is missing.']);
    exit;
}

$raw = file_get_contents($dataFile);
$projects = json_decode($raw, true);

if (!is_array($projects)) {
    http_response_code(500);
    echo json_encode(['error' => 'Project data is not valid JSON.']);
    exit;
}

$statusParam = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$searchParam = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$pageParam = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPageParam = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 6;

$page = max(1, $pageParam);
$perPage = min(25, max(1, $perPageParam));

$normalizedStatus = strtolower($statusParam);
$normalizedSearch = mb_strtolower($searchParam, 'UTF-8');

$availableStatuses = array_values(array_unique(array_map(static function (array $project): string {
    return strtolower((string) ($project['status'] ?? ''));
}, $projects)));
sort($availableStatuses);
$availableStatuses = array_values(array_filter($availableStatuses, static function (string $value): bool {
    return $value !== '';
}));

$filtered = array_filter($projects, static function (array $project) use ($normalizedStatus, $normalizedSearch): bool {
    $matchesStatus = true;
    if ($normalizedStatus !== '') {
        $matchesStatus = strtolower((string) ($project['status'] ?? '')) === $normalizedStatus;
    }

    $matchesSearch = true;
    if ($normalizedSearch !== '') {
        $haystack = mb_strtolower(($project['name'] ?? '') . ' ' . ($project['summary'] ?? ''), 'UTF-8');
        $matchesSearch = mb_strpos($haystack, $normalizedSearch, 0, 'UTF-8') !== false;
    }

    return $matchesStatus && $matchesSearch;
});

$filtered = array_values($filtered);
$total = count($filtered);
$totalPages = $total === 0 ? 1 : (int) ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$items = array_slice($filtered, $offset, $perPage);

$response = [
    'meta' => [
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => $totalPages,
        'hasNextPage' => $page < $totalPages,
        'hasPreviousPage' => $page > 1,
    ],
    'filters' => [
        'status' => $normalizedStatus,
        'search' => $normalizedSearch,
    ],
    'availableStatuses' => $availableStatuses,
    'data' => $items,
];

echo json_encode($response, JSON_PRETTY_PRINT);
