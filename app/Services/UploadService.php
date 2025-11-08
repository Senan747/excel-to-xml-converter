<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use XMLWriter;

class UploadService
{
    public function processUpload(string $filePath)
    {
        $absolutePath = storage_path('app/public/' . $filePath);

        $array = Excel::toArray([], $absolutePath);
        $rows = $array[0] ?? [];

        if (count($rows) < 2) {
            throw new Exception('Faylda başlıq və ya məlumat tapılmadı.');
        }

        $headers = $rows[0];
        unset($rows[0]);

        $xmlData = [];

        foreach ($rows as $row) {
            $item = [];
            foreach ($headers as $key => $header) {
                $cleanHeader = str_replace(' ', '_', trim($header));
                $value = $row[$key] ?? '';
                $item[$cleanHeader] = $value;
            }
            $xmlData[] = $item;
        }

        $xml = $this->generateXml($xmlData);

        $fileName = 'ipn_' . date('Y_m_d_His') . '.xml';
        Storage::disk('public')->put($fileName, $xml);

        $downloadUrl = Storage::url($fileName);

        return [
            'file_name' => $fileName,
            'download_url' => asset($downloadUrl),
        ];
    }

    private function generateXml(array $data)
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);

        $xml->startElement('IPN-LIST');

        foreach ($data as $item) {
            $xml->startElement('IPN');

            $xml->writeElement('FULL-NAME', $item['FULL_NAME'] ?? '');
            $xml->writeElement('DATE-OF-BIRTH', $item['DATE_OF_BIRTH'] ?? '');
            $xml->writeElement('MANDATE-START-DATE', $item['MANDATE_START_DATE'] ?? '');
            $xml->writeElement('MANDATE-END-DATE', $item['MANDATE_END_DATE'] ?? '');

            $roleCode = $item['IDENTIFYING_ROLE_CODE'] ?? 'MU';
            if (strpos($roleCode, '/') !== false) {
                $roleCode = explode('/', $roleCode)[0];
            }
            $xml->writeElement('IDENTIFYING-ROLE-CODE', $roleCode);

            foreach ($item as $key => $value) {
                if (!in_array($key, [
                    'FULL_NAME',
                    'DATE_OF_BIRTH',
                    'MANDATE_START_DATE',
                    'MANDATE_END_DATE',
                    'IDENTIFYING_ROLE_CODE'
                ])) {
                    $xml->writeElement(strtoupper($key), $value);
                }
            }

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
