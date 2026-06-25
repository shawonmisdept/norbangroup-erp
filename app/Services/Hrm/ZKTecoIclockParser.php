<?php

namespace App\Services\Hrm;

/**
 * Parses ZKTeco iClock / ADMS protocol payloads (SpeedFace V5L, uFace, etc.).
 *
 * ATTLOG line format (tab-separated):
 * PIN \t Timestamp \t Status \t VerifyType \t WorkCode ...
 * Status: 0=Check In, 1=Check Out
 * VerifyType: 15=Face (SpeedFace V5L)
 */
class ZKTecoIclockParser
{
    /** @return list<array{user_id: string, punch_time: string, punch_state: string, verify_type: ?string, raw_line: string}> */
    public function parseAttlog(string $body): array
    {
        $records = [];

        foreach (preg_split('/\r\n|\n|\r/', $body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $fields = explode("\t", $line);

            if (count($fields) < 2 || ! is_numeric($fields[0])) {
                continue;
            }

            $records[] = [
                'user_id'     => (string) $fields[0],
                'punch_time'  => trim($fields[1]),
                'punch_state' => isset($fields[2]) ? (string) $fields[2] : '0',
                'verify_type' => $fields[3] ?? null,
                'raw_line'    => $line,
            ];
        }

        return $records;
    }

    public function buildOptionsResponse(string $serial): string
    {
        $timezone = config('hrm.adms.timezone', '+6:00');

        return implode("\r\n", [
            "GET OPTION FROM: {$serial}",
            'Stamp=99999999',
            'OpStamp=99999999',
            'ErrorDelay=60',
            'Delay=30',
            'ResLogDay=18250',
            'ResLogDelCount=10000',
            'ResLogCount=50000',
            'TransTimes=00:00;14:05',
            'TransInterval=1',
            'TransFlag=TransData AttLog OpLog AttPhoto EnrollUser ChgUser EnrollFP ChgFP UserPic',
            "TimeZone={$timezone}",
            'Realtime=1',
            'Encrypt=0',
        ]) . "\r\n";
    }
}
