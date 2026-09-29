<?php

namespace App\Services\DataSources\CoconutBoard;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CoconutBoardDataProvider extends BaseMarketDataProvider
{
    /**
     * Fetch daily coconut and copra rates directly by scraping the Coconut Development Board website HTML tables.
     * Note: Coconut Development Board does not provide a public REST API.
     */
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords();
        }

        $endpoint = $this->dataSource->endpoint ?: '/PriceAppScroll/commodity.aspx';
        $baseUrl = str_contains($this->dataSource->base_url, 'coconutboard.in')
            ? rtrim($this->dataSource->base_url, '/')
            : 'https://coconutboard.in';

        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        $res = $this->makeGetRequest($url, [], [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]);

        if (!$res['success'] || empty($res['body'])) {
            Log::warning("CoconutBoardDataProvider: Live fetch failed or empty response from {$url}.");
            return [];
        }

        // If body is HTML string, parse the market tables
        $records = [];
        if (is_string($res['body'])) {
            $records = $this->scrapeCoconutRatesFromHtml($res['body']);
        } elseif (is_array($res['body']) && isset($res['body']['prices'])) {
            $records = $res['body']['prices'];
        }

        return $records;
    }

    /**
     * Scrape Coconut & Copra rates from HTML table rows.
     * Supports both the live grid table on PriceAppScroll/commodity.aspx and standard row tables.
     */
    protected function scrapeCoconutRatesFromHtml(string $html): array
    {
        $records = [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query('//table//tr');

        if (!$rows || $rows->length === 0) {
            return [];
        }

        $currentCenters = [];

        foreach ($rows as $row) {
            $cells = [];
            foreach ($row->getElementsByTagName('td') as $td) {
                $cells[] = trim(preg_replace('/\s+/', ' ', $td->textContent ?? ''));
            }
            if (empty($cells)) {
                foreach ($row->getElementsByTagName('th') as $th) {
                    $cells[] = trim(preg_replace('/\s+/', ' ', $th->textContent ?? ''));
                }
            }

            if (empty($cells)) {
                continue;
            }

            // Check if this row is a center header row (e.g. Tiptur, Arsikere, Kangayam, Kochi, Pollachi)
            $hasCenterNames = false;
            foreach ($cells as $c) {
                if (preg_match('/(Tiptur|Arsikere|Arisikere|Kochi|Kozhikode|Kangayam|Pollachi|Thrissur|Udumalpet)/i', $c)) {
                    $hasCenterNames = true;
                    break;
                }
            }

            if ($hasCenterNames) {
                $currentCenters = [];
                foreach ($cells as $colIdx => $c) {
                    if (stripos($c, 'Tiptur') !== false) {
                        $currentCenters[$colIdx] = ['center' => 'Tiptur', 'district' => 'Tumakuru', 'state' => 'Karnataka'];
                    } elseif (stripos($c, 'Arisikere') !== false || stripos($c, 'Arsikere') !== false) {
                        $currentCenters[$colIdx] = ['center' => 'Arsikere', 'district' => 'Hassan', 'state' => 'Karnataka'];
                    } elseif (stripos($c, 'Mangalore') !== false || stripos($c, 'Mangaluru') !== false) {
                        $currentCenters[$colIdx] = ['center' => 'Mangaluru', 'district' => 'Dakshina Kannada', 'state' => 'Karnataka'];
                    }
                }
                continue;
            }

            // Check if this row is a commodity price row
            $firstCol = $cells[0] ?? '';
            $commodity = null;
            $grade = 'FAQ';
            $unit = 'Quintal';

            if (stripos($firstCol, 'Ball Copra') !== false) {
                $commodity = 'Copra';
                $grade = 'Ball';
                $unit = 'Quintal';
            } elseif (stripos($firstCol, 'Milling Copra') !== false) {
                $commodity = 'Copra';
                $grade = 'Milling';
                $unit = 'Quintal';
            } elseif (stripos($firstCol, 'Coconut (Rs/Kg)') !== false) {
                $commodity = 'Coconut';
                $grade = 'Dehusked';
                $unit = 'Kg';
            } elseif (stripos($firstCol, 'Desiccated Coconut') !== false) {
                $commodity = 'Copra';
                $grade = 'Desiccated';
                $unit = 'Quintal';
            }

            if ($commodity && !empty($currentCenters)) {
                foreach ($cells as $colIdx => $cellText) {
                    if ($colIdx === 0 || !isset($currentCenters[$colIdx])) {
                        continue;
                    }

                    $centerMeta = $currentCenters[$colIdx];
                    if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*(?:\(([0-9\/]+)\))?/', $cellText, $pMatch)) {
                        $priceVal = (float) $pMatch[1];
                        $dateVal = $pMatch[2] ?? null;

                        if ($priceVal > 0) {
                            $records[] = [
                                'commodity' => $commodity,
                                'grade' => $grade,
                                'center' => $centerMeta['center'],
                                'district' => $centerMeta['district'],
                                'state' => $centerMeta['state'],
                                'modal_price' => (string) $priceVal,
                                'min_price' => (string) round($priceVal * 0.95),
                                'max_price' => (string) round($priceVal * 1.05),
                                'unit' => $unit,
                                'date' => $dateVal,
                            ];
                        }
                    }
                }
                continue;
            }

            // Fallback for single flat row tables only if explicitly mentioning a Karnataka center
            if (empty($currentCenters)) {
                $rowText = implode(' ', $cells);
                $isCopra = stripos($rowText, 'Copra') !== false || stripos($rowText, 'Milling') !== false || stripos($rowText, 'Ball') !== false;
                $isCoconut = stripos($rowText, 'Coconut') !== false || stripos($rowText, 'Dehusked') !== false;

                $hasKarnatakaCenter = false;
                $center = null;
                $district = null;

                if (stripos($rowText, 'Tiptur') !== false) {
                    $hasKarnatakaCenter = true;
                    $center = 'Tiptur';
                    $district = 'Tumakuru';
                } elseif (stripos($rowText, 'Arsikere') !== false || stripos($rowText, 'Arisikere') !== false) {
                    $hasKarnatakaCenter = true;
                    $center = 'Arsikere';
                    $district = 'Hassan';
                } elseif (stripos($rowText, 'Mangalore') !== false || stripos($rowText, 'Mangaluru') !== false) {
                    $hasKarnatakaCenter = true;
                    $center = 'Mangaluru';
                    $district = 'Dakshina Kannada';
                }

                if (($isCopra || $isCoconut) && $hasKarnatakaCenter) {
                    $numbers = [];
                    foreach ($cells as $cell) {
                        // Strip dates like (27/09/2026) so they don't corrupt the number
                        $cleanCell = preg_replace('/\([0-9\/]+\)/', '', $cell);
                        $clean = preg_replace('/[^0-9.]/', '', $cleanCell);
                        if (is_numeric($clean) && (float)$clean >= 500 && (float)$clean <= 100000) {
                            $numbers[] = (float)$clean;
                        }
                    }

                    if (empty($numbers)) {
                        continue;
                    }

                    $comm = $isCopra ? 'Copra' : 'Coconut';
                    $grd = stripos($rowText, 'Ball') !== false ? 'Ball' : (stripos($rowText, 'Milling') !== false ? 'Milling' : 'FAQ');

                    $min = min($numbers);
                    $max = max($numbers);
                    $modal = count($numbers) >= 3 ? $numbers[1] : (($min + $max) / 2);

                    $records[] = [
                        'commodity' => $comm,
                        'grade' => $grd,
                        'center' => $center,
                        'district' => $district,
                        'state' => 'Karnataka',
                        'min_price' => (string) round($min),
                        'max_price' => (string) round($max),
                        'modal_price' => (string) round($modal),
                        'unit' => $comm === 'Copra' ? 'Quintal' : '1000 Nuts',
                    ];
                }
            }
        }

    return $records;
}

    public function normalize(array $record): ?array
    {
        $commodity = $this->applyTransformation($record['commodity'] ?? null, 'trim');
        if (empty($commodity)) {
            return null;
        }

        // Strictly reject any records that do not belong to Karnataka
        if (isset($record['state']) && strcasecmp($record['state'], 'Karnataka') !== 0) {
            return null;
        }

        $min = (float) $this->applyTransformation($record['min_price'] ?? 0, 'to_number');
        $max = (float) $this->applyTransformation($record['max_price'] ?? 0, 'to_number');
        $modal = (float) $this->applyTransformation($record['modal_price'] ?? (($min + $max) / 2), 'to_number');

        $priceDate = Carbon::today()->format('Y-m-d');
        if (!empty($record['date'])) {
            try {
                $priceDate = Carbon::createFromFormat('d/m/Y', $record['date'])->format('Y-m-d');
            } catch (\Throwable) {}
        }

        $unit = $record['unit'] ?? ($commodity === 'Copra' ? 'Quintal' : '1000 Nuts');
        if ($unit === 'Kg') {
            // Normalize per Kg price to Quintal (100 Kg)
            $min *= 100;
            $max *= 100;
            $modal *= 100;
            $unit = 'Quintal';
        }

        return [
            'source_crop' => (stripos($commodity, 'Copra') !== false ? 'Copra' : (stripos($commodity, 'Tender') !== false ? 'Tender Coconut' : 'Coconut')),
            'source_variety' => $this->applyTransformation($record['grade'] ?? 'FAQ', 'trim'),
            'source_market' => $this->applyTransformation($record['center'] ?? 'Arsikere', 'trim'),
            'source_district' => $this->applyTransformation($record['district'] ?? 'Hassan', 'trim'),
            'price_date' => $priceDate,
            'min_price' => round($min, 2),
            'max_price' => round($max, 2),
            'modal_price' => round($modal, 2),
            'arrival_quantity' => 0.0,
            'unit' => $unit,
            'raw_payload' => $record,
        ];
    }

    public function healthCheck(): array
    {
        if ($this->isMockMode()) {
            return [
                'http_status' => 200,
                'response_time_ms' => 45,
                'auth_result' => 'success (mock/scraper mode)',
                'records_found' => 3,
                'detected_fields' => ['commodity', 'grade', 'center', 'district', 'min_price', 'max_price', 'modal_price', 'unit'],
                'status' => 'healthy',
                'error_message' => null,
                'sample_payload' => $this->getMockRecords()[0] ?? [],
            ];
        }

        $endpoint = $this->dataSource->endpoint ?: '/PriceAppScroll/commodity.aspx';
        $baseUrl = str_contains($this->dataSource->base_url, 'coconutboard.in')
            ? rtrim($this->dataSource->base_url, '/')
            : 'https://coconutboard.in';

        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        $res = $this->makeGetRequest($url, [], [
            'Accept' => 'text/html,application/xhtml+xml',
        ]);

        $records = [];
        if ($res['success'] && is_string($res['body'])) {
            $records = $this->scrapeCoconutRatesFromHtml($res['body']);
        }

        return [
            'http_status' => $res['http_status'] ?? ($res['success'] ? 200 : null),
            'response_time_ms' => $res['response_time_ms'],
            'auth_result' => $res['success'] ? 'success (web scraper)' : 'failed',
            'records_found' => count($records),
            'detected_fields' => !empty($records) ? ['commodity', 'grade', 'center', 'district', 'min_price', 'max_price', 'modal_price', 'unit'] : [],
            'status' => $res['success'] && !empty($records) ? 'healthy' : 'degraded',
            'error_message' => $res['error'],
            'sample_payload' => $records[0] ?? null,
        ];
    }

    protected function getMockRecords(): array
    {
        return [
            [
                'commodity' => 'Copra',
                'grade' => 'Milling Copra',
                'center' => 'Arsikere',
                'district' => 'Hassan',
                'min_price' => '11800',
                'max_price' => '12600',
                'modal_price' => '12200',
                'unit' => 'Quintal',
            ],
            [
                'commodity' => 'Copra',
                'grade' => 'Ball Copra',
                'center' => 'Tiptur',
                'district' => 'Tumakuru',
                'min_price' => '16500',
                'max_price' => '18200',
                'modal_price' => '17400',
                'unit' => 'Quintal',
            ],
            [
                'commodity' => 'Coconut',
                'grade' => 'Dehusked Large (Grade A)',
                'center' => 'Mangaluru',
                'district' => 'Dakshina Kannada',
                'min_price' => '24000',
                'max_price' => '26500',
                'modal_price' => '25000',
                'unit' => '1000 Nuts',
            ],
            [
                'commodity' => 'Coconut',
                'grade' => 'Dehusked Medium (Grade B)',
                'center' => 'Tumakuru',
                'district' => 'Tumakuru',
                'min_price' => '21000',
                'max_price' => '23500',
                'modal_price' => '22500',
                'unit' => '1000 Nuts',
            ],
            [
                'commodity' => 'Coconut',
                'grade' => 'With Husk (Field Run)',
                'center' => 'Arsikere',
                'district' => 'Hassan',
                'min_price' => '18000',
                'max_price' => '20000',
                'modal_price' => '19000',
                'unit' => '1000 Nuts',
            ],
            [
                'commodity' => 'Tender Coconut',
                'grade' => 'Large Tender Coconut',
                'center' => 'Tumakuru',
                'district' => 'Tumakuru',
                'min_price' => '32000',
                'max_price' => '36000',
                'modal_price' => '34000',
                'unit' => '1000 Nuts',
            ],
        ];
    }
}
