<?php

namespace App\Exports;

use App\Models\Reservation;
use App\Enums\ReservationStatus;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ReservationsExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    public function __construct(
        private ?string $platformId = null,
        private ?string $status     = null,
        private ?string $dateFrom   = null,
        private ?string $dateTo     = null,
    ) {}

    public function query()
    {
        $query = Reservation::query()
            ->with(['parkingListing.platform', 'parkingListing.parking'])
            ->orderByDesc('starts_at');

        if ($this->platformId) {
            $query->whereHas('parkingListing', fn($q) =>
                $q->where('platform_id', $this->platformId)
            );
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom) {
            $query->where('starts_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('ends_at', '<=', $this->dateTo . ' 23:59:59');
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Cliente',
            'Email',
            'Telefono',
            'Targa',
            'Canale',
            'Parcheggio',
            'Arrivo',
            'Partenza',
            'Posti',
            'Prezzo (€)',
            'Stato',
            'Codice esterno',
            'Note',
            'Creata il',
        ];
    }

    public function map($reservation): array
    {
        return [
            $reservation->id,
            $reservation->customer_name,
            $reservation->customer_email ?? '',
            $reservation->customer_phone ?? '',
            $reservation->license_plate ?? '',
            $reservation->parkingListing->platform->name,
            $reservation->parkingListing->parking->name,
            $reservation->starts_at->format('d/m/Y H:i'),
            $reservation->ends_at->format('d/m/Y H:i'),
            $reservation->spots,
            $reservation->price ? number_format($reservation->price, 2, ',', '.') : '',
            $reservation->status->label(),
            $reservation->external_id ?? '',
            $reservation->notes ?? '',
            $reservation->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => 'solid',
                    'startColor' => ['rgb' => '1e293b'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 22,
            'C' => 28,
            'D' => 16,
            'E' => 12,
            'F' => 18,
            'G' => 22,
            'H' => 16,
            'I' => 16,
            'J' => 8,
            'K' => 12,
            'L' => 14,
            'M' => 18,
            'N' => 30,
            'O' => 16,
        ];
    }

    public function title(): string
    {
        return 'Prenotazioni';
    }
}