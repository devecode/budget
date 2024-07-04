<?php

namespace App\Exports;

use App\Models\Egress;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EgresosExport implements FromQuery, WithHeadings
{
    use Exportable;

    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return Egress::with('category', 'paymentMethod')
            ->where('payment_method_id', function ($query) {
                $query->select('id')
                    ->from('payment_methods')
                    ->where('name', 'Credit Card');
            })
            ->where('status', 'pendiente')
            ->whereBetween('date_egreso', [$this->startDate, $this->endDate]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Categoría',
            'Monto',
            'Fecha de pago',
            'Método de Pago',
        ];
    }
}

