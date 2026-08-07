<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CompanySettingsService
{
    public const DEFAULT_REPAIR_WARRANTY = 'Garantía de 30 días por mano de obra aplicando términos y condiciones. No cubre daños por mal uso, agua, golpes o manipulación por terceros después de la entrega.';

    private const GENERAL_KEYS = [
        'company_name',
        'company_legal_name',
        'company_ruc',
        'company_phone',
        'company_email',
        'company_address',
        'company_city',
        'company_country',
        'currency',
        'currency_symbol',
        'timezone',
        'date_format',
        'language',
        'company_logo',
        'ticket_logo',
        'invoice_footer',
        'receipt_message',
        'repair_warranty_text',
    ];

    private const DEFAULTS = [
        'company_name' => 'Mi Empresa',
        'company_legal_name' => '',
        'company_ruc' => '',
        'company_phone' => '',
        'company_email' => '',
        'company_address' => '',
        'company_city' => 'Estelí',
        'company_country' => 'Nicaragua',
        'currency' => 'NIO',
        'currency_symbol' => 'C$',
        'timezone' => 'America/Managua',
        'date_format' => 'd/m/Y',
        'language' => 'es',
        'company_logo' => '',
        'ticket_logo' => '',
        'invoice_footer' => '',
        'receipt_message' => '¡Gracias por su compra!',
        'repair_warranty_text' => self::DEFAULT_REPAIR_WARRANTY,
        'system_name' => 'EsteliPOS',
    ];

    public function get(): array
    {
        $settings = array_merge(
            self::DEFAULTS,
            Setting::getByGroup('general'),
            Arr::only(Setting::getByGroup('appearance'), ['system_name']),
        );

        if ($settings['currency'] === 'C$') {
            $settings['currency'] = 'NIO';
        }

        $settings['company_logo_url'] = $this->publicUrl($settings['company_logo']);
        $settings['ticket_logo_url'] = $this->publicUrl($settings['ticket_logo']);

        return $settings;
    }

    public function update(array $data, ?UploadedFile $companyLogo = null, ?UploadedFile $ticketLogo = null): array
    {
        $old = $this->get();
        $newFiles = [];
        $nextCompanyLogo = $old['company_logo'];
        $nextTicketLogo = $old['ticket_logo'];

        try {
            if ($companyLogo) {
                $nextCompanyLogo = $companyLogo->store('company', 'public');
                if (! $nextCompanyLogo) {
                    throw new RuntimeException('No fue posible guardar el logo principal.');
                }
                $newFiles[] = $nextCompanyLogo;
            } elseif ($data['remove_company_logo'] ?? false) {
                $nextCompanyLogo = '';
            }

            if ($ticketLogo) {
                $nextTicketLogo = $ticketLogo->store('company', 'public');
                if (! $nextTicketLogo) {
                    throw new RuntimeException('No fue posible guardar el logo para tickets.');
                }
                $newFiles[] = $nextTicketLogo;
            } elseif ($data['remove_ticket_logo'] ?? false) {
                $nextTicketLogo = '';
            }

            $payload = Arr::only($data, self::GENERAL_KEYS);
            $payload['company_logo'] = $nextCompanyLogo;
            $payload['ticket_logo'] = $nextTicketLogo;

            DB::transaction(function () use ($payload, $data, $old): void {
                foreach ($payload as $key => $value) {
                    Setting::set($key, $value, null, 'general');
                }

                Setting::set('system_name', $data['system_name'], null, 'appearance');

                $newValues = array_merge($payload, ['system_name' => $data['system_name']]);
                AuditLog::log(
                    'settings.company.updated',
                    'Se actualizó la configuración de empresa.',
                    null,
                    Arr::only($old, array_keys($newValues)),
                    $newValues,
                );
            });
        } catch (Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        $this->deleteReplacedFile($old['company_logo'], $nextCompanyLogo);
        $this->deleteReplacedFile($old['ticket_logo'], $nextTicketLogo);

        return $this->get();
    }

    private function publicUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return url('/storage/'.ltrim($path, '/'));
    }

    private function deleteReplacedFile(?string $oldPath, ?string $newPath): void
    {
        if ($oldPath && $oldPath !== $newPath && str_starts_with($oldPath, 'company/')) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
