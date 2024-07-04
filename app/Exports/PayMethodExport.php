<?php

namespace App\Exports;

use App\Models\Egress;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayMethodExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $paymentMethod;

    public function __construct($startDate, $endDate, $paymentMethod)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->paymentMethod = $paymentMethod;
    }

    public function query()
    {
        $query = Egress::query()
            ->join('categories', 'egresses.category_id', '=', 'categories.id')
            ->join('payment_methods', 'egresses.payment_method_id', '=', 'payment_methods.id')
            ->select(
                'payment_methods.name as payment_method',
                'categories.name as category',
                'categories.limit_spending',
                'egresses.amount',
                'egresses.date_egreso'
            )
            ->whereBetween('egresses.date_egreso', [$this->startDate, $this->endDate]);

        if ($this->paymentMethod) {
            $query->where('payment_methods.name', $this->paymentMethod);
        }

        return $query->orderBy('egresses.id', 'asc');
    }

    public function headings(): array
    {
        return [
            'Método de Pago',
            'Categoría',
            'Límite de Gasto',
            'Monto',
            'Fecha de Egreso'
        ];
    }

    public function map($row): array
    {
        return [
            $row->payment_method,
            $row->category,
            $row->limit_spending,
            $row->amount,
            $row->date_egreso,
        ];
    }
}



