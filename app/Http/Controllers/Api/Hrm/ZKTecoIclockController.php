<?php

namespace App\Http\Controllers\Api\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\BiometricDevice;
use App\Services\Hrm\AttendancePunchService;
use App\Services\Hrm\ZKTecoIclockParser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ZKTeco SpeedFace V5L / iClock ADMS protocol endpoints.
 *
 * Device Cloud Server Setting:
 *   Server Address: http://YOUR-SERVER-IP:PORT
 *   Server Port:    8000 (or your web port)
 * The device appends /iclock/cdata automatically.
 */
class ZKTecoIclockController extends Controller
{
    public function cdataGet(Request $request, ZKTecoIclockParser $parser): Response
    {
        $serial = (string) $request->query('SN', '');
        $this->touchDevice($serial);

        return response($parser->buildOptionsResponse($serial), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    public function cdataPost(
        Request $request,
        ZKTecoIclockParser $parser,
        AttendancePunchService $punchService
    ): Response {
        $serial = (string) $request->query('SN', '');
        $table = (string) $request->query('table', '');
        $body = $request->getContent();

        $device = $this->resolveDevice($serial);

        if ($device && $table === 'ATTLOG' && filled($body)) {
            $records = $parser->parseAttlog($body);

            foreach ($records as $record) {
                $record['device_serial'] = $serial;
                $punchService->recordFromDevice($device, $record, 'iclock_push');
            }

            $device->update([
                'last_synced_at'    => now(),
                'last_seen_at'      => now(),
                'last_sync_status'  => 'success',
                'last_sync_message' => 'SpeedFace push: ' . count($records) . ' log(s)',
            ]);
        } elseif ($device) {
            $device->update(['last_seen_at' => now()]);
        }

        return response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function getRequest(Request $request): Response
    {
        $this->touchDevice((string) $request->query('SN', ''));

        return response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function deviceCmd(Request $request): Response
    {
        $this->touchDevice((string) $request->query('SN', ''));

        return response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function registry(Request $request, ZKTecoIclockParser $parser): Response
    {
        $serial = (string) $request->query('SN', '');

        if ($request->isMethod('GET')) {
            return response($parser->buildOptionsResponse($serial), 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $this->touchDevice($serial);

        return response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function resolveDevice(string $serial): ?BiometricDevice
    {
        if ($serial === '') {
            return null;
        }

        return BiometricDevice::query()
            ->where('device_serial', $serial)
            ->where('is_active', true)
            ->first();
    }

    private function touchDevice(string $serial): void
    {
        if ($serial === '') {
            return;
        }

        BiometricDevice::query()
            ->where('device_serial', $serial)
            ->update(['last_seen_at' => now()]);
    }
}
