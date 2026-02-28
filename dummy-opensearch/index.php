<?php
header('Content-Type: application/json');

$uri = $_SERVER['REQUEST_URI'] ?? '/';

$cluster = [
	'name' => 'dummy',
	'cluster_name' => 'dummy_cluster',
	'cluster_uuid' => 'abcd',
	'version' => ['number' => '2.12.0', 'lucene_version' => '9.9.1'],
	'tagline' => 'The OpenSearch Project: https://opensearch.org/',
];

if (strpos($uri, '_cluster/health') !== false) {
	echo json_encode($cluster + ['status' => 'green', 'number_of_nodes' => 1]);
	exit;
}

if (strpos($uri, '_cat/indices') !== false) {
	header('Content-Type: text/plain');
	echo "health status index uuid pri rep docs.count docs.deleted store.size pri.store.size\n";
	echo "green  open   dummy-index abcd 1   0   0          0            0b         0b\n";
	exit;
}

if (strpos($uri, '_search') !== false || strpos($uri, '_msearch') !== false) {
	$emptyResponse = [
		'took' => 1,
		'timed_out' => false,
		'hits' => ['total' => ['value' => 0, 'relation' => 'eq'], 'max_score' => 0, 'hits' => []],
		'aggregations' => new stdClass(),
	];
	echo json_encode($emptyResponse);
	exit;
}

echo json_encode($cluster);
