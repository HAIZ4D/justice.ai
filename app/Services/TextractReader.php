<?php

namespace App\Services;

use Aws\Textract\TextractClient;

class TextractReader
{
    private $client;

    public function __construct()
    {
        $this->client = new TextractClient([
            'version' => 'latest',
            'region'  => env('TEXTRACT_REGION', 'ap-southeast-1'), // Singapore
        ]);
    }

    public function extract(string $bucket, string $key): array
    {
        $job = $this->client->startDocumentAnalysis([
            'DocumentLocation' => [
                'S3Object' => ['Bucket' => $bucket, 'Name' => $key],
            ],
            'FeatureTypes' => ['TABLES', 'FORMS'],
        ]);

        $jobId = $job['JobId'];

        // Wait for job to complete
        do {
            sleep(2);
            $resp = $this->client->getDocumentAnalysis(['JobId' => $jobId]);
            $status = $resp['JobStatus'];
        } while ($status === 'IN_PROGRESS');

        if ($status !== 'SUCCEEDED') {
            throw new \RuntimeException("Textract job failed: {$status}");
        }

        $lines = [];
        foreach ($resp['Blocks'] as $b) {
            if ($b['BlockType'] === 'LINE' && isset($b['Text'])) {
                $lines[] = [
                    'page' => $b['Page'],
                    'text' => $b['Text'],
                    'bbox' => $b['Geometry']['BoundingBox'],
                ];
            }
        }
        return $lines;
    }
}
